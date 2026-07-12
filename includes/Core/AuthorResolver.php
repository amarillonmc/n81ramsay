<?php
/**
 * 作者归属解析器
 *
 * 将管理员文本规则、管理员卡号区间、CDB署名与strings.conf兜底统一到
 * 一条可解释的判定链中。该类不访问数据库，便于所有调用方复用和测试。
 */
class AuthorResolver {
    /**
     * 未知作者名称
     */
    const UNKNOWN_AUTHOR = '未知作者';

    /**
     * 解析卡片作者
     *
     * 优先级：管理员文本规则 > 管理员卡号区间 > CDB署名 > strings.conf。
     *
     * @param array $card 卡片数据
     * @param array $manualMappings 管理员卡号区间
     * @param array $textRules 管理员文本规则
     * @param array $fallbackMappings strings.conf区间
     * @param bool $manualOnly 是否仅使用管理员规则
     * @return array 解析结果
     */
    public function resolve($card, $manualMappings = [], $textRules = [], $fallbackMappings = [], $manualOnly = false) {
        $textMatch = $this->findTextRuleMatch($card, $textRules, 'author');
        if ($textMatch !== null) {
            return $this->createResult(
                $this->sanitizeKnownAuthorName($this->getRuleTargetValue($textMatch['rule'])),
                'manual_text',
                '管理员文本规则',
                isset($textMatch['rule']['id']) ? (int)$textMatch['rule']['id'] : null,
                $textMatch['field'],
                $textMatch['rule']['match_value']
            );
        }

        $prefixMatch = $this->findPrefixMatch($card, $manualMappings);
        if ($prefixMatch !== null) {
            return $this->createResult(
                $this->sanitizeKnownAuthorName($prefixMatch['author_name']),
                'manual_prefix',
                '管理员卡号区间',
                isset($prefixMatch['id']) ? (int)$prefixMatch['id'] : null,
                'id',
                $this->describeMapping($prefixMatch)
            );
        }

        if ($manualOnly) {
            return $this->createUnknownResult();
        }

        $signature = $this->extractSignature($card);
        if ($signature !== null) {
            $signatureAuthor = $this->canonicalizeAuthorAlias($signature['author'], $manualMappings);
            return $this->createResult(
                $signatureAuthor,
                'signature',
                'CDB署名',
                null,
                $signature['field'],
                $signature['line']
            );
        }

        $fallbackMatch = $this->findPrefixMatch($card, $fallbackMappings);
        if ($fallbackMatch !== null) {
            return $this->createResult(
                $this->sanitizeKnownAuthorName($fallbackMatch['author_name']),
                'strings_conf',
                'strings.conf区间',
                null,
                'id',
                $this->describeMapping($fallbackMatch)
            );
        }

        return $this->createUnknownResult();
    }

    /**
     * 解析管理员文本规则指定的人工系列分组
     *
     * 系列目标与作者目标完全分离，不参与作者榜或作者身份授权。它提供独立的
     * 展示与同系列推荐元数据，也不会伪造或改写CDB的数值setcode。
     *
     * @param array $card 卡片数据
     * @param array $textRules 管理员文本规则
     * @return array|null 系列分组及命中来源，未命中时为null
     */
    public function resolveSeries($card, $textRules = []) {
        $textMatch = $this->findTextRuleMatch($card, $textRules, 'series');
        if ($textMatch === null) {
            return null;
        }

        $seriesName = $this->sanitizeKnownAuthorName($this->getRuleTargetValue($textMatch['rule']));
        if ($seriesName === self::UNKNOWN_AUTHOR) {
            return null;
        }

        return [
            'series_name' => $seriesName,
            'source' => 'manual_text',
            'source_label' => '管理员文本系列规则',
            'rule_id' => isset($textMatch['rule']['id']) ? (int)$textMatch['rule']['id'] : null,
            'matched_on' => $textMatch['field'],
            'matched_value' => $textMatch['rule']['match_value']
        ];
    }

    /**
     * 查找优先级最高的文本规则
     *
     * @param array $card 卡片数据
     * @param array $rules 文本规则
     * @param string $targetType 规则目标类型
     * @return array|null 命中信息
     */
    private function findTextRuleMatch($card, $rules, $targetType) {
        $enabledRules = array_values(array_filter($rules, function($rule) use ($targetType) {
            if (isset($rule['is_enabled']) && (int)$rule['is_enabled'] !== 1) {
                return false;
            }
            return $this->getRuleTargetType($rule) === $targetType &&
                $this->getRuleTargetValue($rule) !== '' &&
                isset($rule['match_value']) && trim((string)$rule['match_value']) !== '';
        }));

        usort($enabledRules, function($left, $right) {
            $leftPriority = isset($left['priority']) ? (int)$left['priority'] : 100;
            $rightPriority = isset($right['priority']) ? (int)$right['priority'] : 100;
            if ($leftPriority !== $rightPriority) {
                return $rightPriority <=> $leftPriority;
            }

            $leftOperator = isset($left['match_operator']) ? $left['match_operator'] : 'contains';
            $rightOperator = isset($right['match_operator']) ? $right['match_operator'] : 'contains';
            $operatorWeights = ['equals' => 3, 'line_equals' => 2, 'contains' => 1];
            $leftWeight = isset($operatorWeights[$leftOperator]) ? $operatorWeights[$leftOperator] : 0;
            $rightWeight = isset($operatorWeights[$rightOperator]) ? $operatorWeights[$rightOperator] : 0;
            if ($leftWeight !== $rightWeight) {
                return $rightWeight <=> $leftWeight;
            }

            $leftLength = strlen((string)$left['match_value']);
            $rightLength = strlen((string)$right['match_value']);
            if ($leftLength !== $rightLength) {
                return $rightLength <=> $leftLength;
            }

            return (isset($left['id']) ? (int)$left['id'] : 0) <=>
                (isset($right['id']) ? (int)$right['id'] : 0);
        });

        foreach ($enabledRules as $rule) {
            if (!$this->matchesDatabase($card, $rule)) {
                continue;
            }

            $field = isset($rule['match_field']) ? (string)$rule['match_field'] : 'desc';
            $values = $this->getTextValues($card, $field);
            if ($values === null) {
                continue;
            }

            foreach ($values as $value) {
                if ($this->matchesTextValue($value, $rule)) {
                    return ['rule' => $rule, 'field' => $field];
                }
            }
        }

        return null;
    }

    /**
     * 获取规则目标类型，并兼容迁移前仅有author_name的规则
     *
     * @param array $rule 文本规则
     * @return string author或series
     */
    private function getRuleTargetType($rule) {
        $targetType = isset($rule['target_type']) ? strtolower(trim((string)$rule['target_type'])) : 'author';
        return $targetType === 'series' ? 'series' : 'author';
    }

    /**
     * 获取规则目标值，并兼容旧author_name列
     *
     * @param array $rule 文本规则
     * @return string 目标名称
     */
    private function getRuleTargetValue($rule) {
        if (isset($rule['target_value']) && trim((string)$rule['target_value']) !== '') {
            return trim((string)$rule['target_value']);
        }
        if ($this->getRuleTargetType($rule) === 'author' && isset($rule['author_name'])) {
            return trim((string)$rule['author_name']);
        }
        return '';
    }

    /**
     * 使用管理员维护的别名把CDB署名归一到权威作者名
     *
     * @param string $authorName CDB署名
     * @param array $mappings 管理员映射
     * @return string 权威作者名或原署名
     */
    private function canonicalizeAuthorAlias($authorName, $mappings) {
        $authorName = trim((string)$authorName);
        foreach ($mappings as $mapping) {
            if (!isset($mapping['author_name'])) {
                continue;
            }

            $canonicalName = trim((string)$mapping['author_name']);
            if ($canonicalName === $authorName) {
                return $this->sanitizeKnownAuthorName($canonicalName);
            }

            if (empty($mapping['alias'])) {
                continue;
            }
            foreach (explode(',', $mapping['alias']) as $alias) {
                if (trim($alias) === $authorName) {
                    return $this->sanitizeKnownAuthorName($canonicalName);
                }
            }
        }

        return $authorName;
    }

    /**
     * 检查规则的数据库限定
     *
     * @param array $card 卡片数据
     * @param array $rule 规则
     * @return bool 是否匹配
     */
    private function matchesDatabase($card, $rule) {
        $databaseFile = isset($rule['database_file']) ? trim((string)$rule['database_file']) : '';
        if ($databaseFile === '' || $databaseFile === '*') {
            return true;
        }

        $cardDatabase = isset($card['database_file']) ? basename((string)$card['database_file']) : '';
        return $cardDatabase !== '' && strcasecmp(basename($databaseFile), $cardDatabase) === 0;
    }

    /**
     * 获取指定CDB文本字段
     *
     * @param array $card 卡片数据
     * @param string $field 字段
     * @return array|null 文本值
     */
    private function getTextValues($card, $field) {
        $validFields = ['name', 'desc'];
        for ($index = 1; $index <= 16; $index++) {
            $validFields[] = 'str' . $index;
        }

        if ($field === 'any') {
            $values = [];
            foreach ($validFields as $validField) {
                if (isset($card[$validField]) && (string)$card[$validField] !== '') {
                    $values[] = (string)$card[$validField];
                }
            }
            return $values;
        }

        if (!in_array($field, $validFields, true)) {
            return null;
        }

        return [isset($card[$field]) ? (string)$card[$field] : ''];
    }

    /**
     * 检查文本值
     *
     * @param string $value CDB文本
     * @param array $rule 规则
     * @return bool 是否匹配
     */
    private function matchesTextValue($value, $rule) {
        $operator = isset($rule['match_operator']) ? (string)$rule['match_operator'] : 'contains';
        if (!in_array($operator, ['contains', 'equals', 'line_equals'], true)) {
            return false;
        }

        $candidate = $this->normalizeLineEndings($value);
        $needle = $this->normalizeLineEndings((string)$rule['match_value']);
        $caseSensitive = isset($rule['is_case_sensitive']) && (int)$rule['is_case_sensitive'] === 1;

        if (!$caseSensitive) {
            $candidate = $this->lowercase($candidate);
            $needle = $this->lowercase($needle);
        }

        if ($operator === 'equals') {
            return trim($candidate) === trim($needle);
        }

        if ($operator === 'line_equals') {
            $needle = trim($needle);
            foreach (explode("\n", $candidate) as $line) {
                $line = trim($line);
                $line = preg_replace('/^[-—–_:：\s]+/u', '', $line);
                if ($line === $needle) {
                    return true;
                }
            }
            return false;
        }

        return strpos($candidate, $needle) !== false;
    }

    /**
     * 查找最具体的卡号区间
     *
     * @param array $card 卡片数据
     * @param array $mappings 区间列表
     * @return array|null 命中的区间
     */
    private function findPrefixMatch($card, $mappings) {
        if (!isset($card['id']) || !preg_match('/^\d+$/', (string)$card['id'])) {
            return null;
        }

        $matches = [];
        foreach ($mappings as $mapping) {
            if (!isset($mapping['author_name']) || trim((string)$mapping['author_name']) === '') {
                continue;
            }
            if ($this->mappingMatchesCardId((string)$card['id'], $mapping)) {
                $matches[] = $mapping;
            }
        }

        if (empty($matches)) {
            return null;
        }

        usort($matches, function($left, $right) {
            $leftPriority = isset($left['priority']) ? (int)$left['priority'] : 100;
            $rightPriority = isset($right['priority']) ? (int)$right['priority'] : 100;
            if ($leftPriority !== $rightPriority) {
                return $rightPriority <=> $leftPriority;
            }

            $leftSpecificity = $this->getMappingSpecificity($left);
            $rightSpecificity = $this->getMappingSpecificity($right);
            if ($leftSpecificity !== $rightSpecificity) {
                return $rightSpecificity <=> $leftSpecificity;
            }

            return (isset($left['id']) ? (int)$left['id'] : 0) <=>
                (isset($right['id']) ? (int)$right['id'] : 0);
        });

        return $matches[0];
    }

    /**
     * 检查区间是否包含卡号
     *
     * @param string $cardId 卡号
     * @param array $mapping 区间
     * @return bool 是否包含
     */
    private function mappingMatchesCardId($cardId, $mapping) {
        if (isset($mapping['card_id_start'], $mapping['card_id_end']) &&
            $mapping['card_id_start'] !== null && $mapping['card_id_end'] !== null &&
            $mapping['card_id_start'] !== '' && $mapping['card_id_end'] !== '') {
            $id = (int)$cardId;
            return $id >= (int)$mapping['card_id_start'] && $id <= (int)$mapping['card_id_end'];
        }

        if (!isset($mapping['card_prefix'])) {
            return false;
        }

        $prefix = trim((string)$mapping['card_prefix']);
        if (!preg_match('/^\d{1,16}$/', $prefix)) {
            return false;
        }

        $prefixWidth = max(3, strlen($prefix));
        $canonicalPrefix = str_pad($prefix, $prefixWidth, '0', STR_PAD_LEFT);
        $cardIdLength = isset($mapping['card_id_length']) && (int)$mapping['card_id_length'] > 0
            ? (int)$mapping['card_id_length']
            : max(8, min(16, strlen($canonicalPrefix) + 5));

        if ($cardIdLength < strlen($canonicalPrefix) || strlen($cardId) > $cardIdLength) {
            return false;
        }

        $canonicalCardId = str_pad($cardId, $cardIdLength, '0', STR_PAD_LEFT);
        return strpos($canonicalCardId, $canonicalPrefix) === 0;
    }

    /**
     * 获取区间具体程度
     *
     * @param array $mapping 区间
     * @return int 分值
     */
    private function getMappingSpecificity($mapping) {
        if (isset($mapping['card_id_start'], $mapping['card_id_end']) &&
            $mapping['card_id_start'] !== '' && $mapping['card_id_end'] !== '') {
            $span = max(0, (int)$mapping['card_id_end'] - (int)$mapping['card_id_start']);
            return 1000000000 - min(999999999, $span);
        }

        return isset($mapping['card_prefix']) ? strlen((string)$mapping['card_prefix']) : 0;
    }

    /**
     * 从CDB文本提取署名
     *
     * @param array $card 卡片数据
     * @return array|null 署名信息
     */
    private function extractSignature($card) {
        $fields = ['desc'];
        for ($index = 1; $index <= 16; $index++) {
            $fields[] = 'str' . $index;
        }

        $marker = '(?:Do\s*It\s*(?:By\s*)?Yourself|DoltYourself|Doityouself|D[IiLl]Y)';
        foreach ($fields as $field) {
            if (!isset($card[$field]) || trim((string)$card[$field]) === '') {
                continue;
            }

            $text = $this->normalizeLineEndings((string)$card[$field]);
            foreach (explode("\n", $text) as $line) {
                $author = null;
                $isCardDesignSignature = false;

                // 常规格式也允许署名标记被书名号等括起，例如「DoItYourself」by 作者。
                $leadingMarkerPattern = '/' . $marker .
                    '\s*[」』】》〉）\)\]”"]*\s*(?:[-—–_:：=]+\s*|by\s+|\s+)(.+)$/iu';
                if (preg_match($leadingMarkerPattern, $line, $matches)) {
                    $author = $matches[1];
                } elseif (preg_match(
                    '/^\s*[-—–_:：]*\s*Card\s*Design\s+by\s+(.+)$/iu',
                    $line,
                    $matches
                )) {
                    // CardDesign必须从独立行开头出现，避免把效果正文中的英文短语当作署名。
                    $author = $matches[1];
                    $isCardDesignSignature = true;
                } elseif (preg_match(
                    '/^\s*([A-Za-z0-9_][A-Za-z0-9_.+@#& ()\[\]\-]{0,63}?)' .
                    '\s*[-—–_:：=]{2,}\s*' . $marker . '\s*$/iu',
                    $line,
                    $matches
                )) {
                    // 反向格式仅接受短ASCII风格账号，避免“效果正文--DoItYourself”被误判。
                    $author = $matches[1];
                }

                if ($author === null) {
                    continue;
                }

                if ($isCardDesignSignature) {
                    // 署名后的「作品名」不是作者名，例如 Justfish 「Besessenheit」。
                    $author = preg_replace('/\s+[「『【《〈“"].*$/u', '', trim($author));
                }
                $author = preg_replace(
                    '/\s*(?:\/|\||;|；)\s*(?:Pic(?:ture)?|Illustrator|PSCT|Script|Lua)\b.*$/iu',
                    '',
                    trim($author)
                );
                $author = $this->normalizeSignatureName($author);
                if ($author !== self::UNKNOWN_AUTHOR) {
                    return ['author' => $author, 'field' => $field, 'line' => trim($line)];
                }
            }
        }

        return null;
    }

    /**
     * 清理权威来源中的作者名，不进行启发式截断
     *
     * @param string $authorName 作者名
     * @return string 清理后的作者名
     */
    private function sanitizeKnownAuthorName($authorName) {
        $authorName = trim((string)$authorName);
        if ($authorName !== '' && preg_match('//u', $authorName) !== 1) {
            if (function_exists('mb_convert_encoding')) {
                $authorName = mb_convert_encoding($authorName, 'UTF-8', 'auto');
            } else {
                return self::UNKNOWN_AUTHOR;
            }
        }

        $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $authorName);
        $authorName = $cleaned === null ? '' : $cleaned;
        return $authorName === '' ? self::UNKNOWN_AUTHOR : $authorName;
    }

    /**
     * 对非结构化CDB署名做保守清理
     *
     * @param string $authorName 原始署名
     * @return string 作者名
     */
    private function normalizeSignatureName($authorName) {
        $authorName = $this->sanitizeKnownAuthorName($authorName);
        if ($authorName === self::UNKNOWN_AUTHOR) {
            return $authorName;
        }

        $commonSuffixes = ['图侵删歉', '图侵删', '侵删', '图源网络', '图片来源网络'];
        foreach ($commonSuffixes as $suffix) {
            $position = strpos($authorName, $suffix);
            if ($position !== false) {
                $authorName = trim(substr($authorName, 0, $position));
            }
        }

        return $authorName === '' ? self::UNKNOWN_AUTHOR : $authorName;
    }

    /**
     * 统一文本换行
     *
     * @param string $value 文本
     * @return string 统一后的文本
     */
    private function normalizeLineEndings($value) {
        return str_replace(["\r\n", "\r"], "\n", (string)$value);
    }

    /**
     * Unicode可用时执行Unicode小写转换
     *
     * @param string $value 文本
     * @return string 小写文本
     */
    private function lowercase($value) {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }
        return strtolower($value);
    }

    /**
     * 描述卡号区间
     *
     * @param array $mapping 区间
     * @return string 描述
     */
    private function describeMapping($mapping) {
        if (isset($mapping['card_id_start'], $mapping['card_id_end']) &&
            $mapping['card_id_start'] !== '' && $mapping['card_id_end'] !== '') {
            return (string)$mapping['card_id_start'] . '-' . (string)$mapping['card_id_end'];
        }

        $prefix = isset($mapping['card_prefix']) ? (string)$mapping['card_prefix'] : '';
        $length = isset($mapping['card_id_length']) ? (int)$mapping['card_id_length'] : 0;
        return $length > 0 ? $prefix . '（' . $length . '位）' : $prefix;
    }

    /**
     * 创建解析结果
     *
     * @param string $author 作者
     * @param string $source 来源代码
     * @param string $sourceLabel 来源名称
     * @param int|null $ruleId 规则ID
     * @param string|null $matchedOn 命中字段
     * @param string|null $matchedValue 命中值
     * @return array 结果
     */
    private function createResult($author, $source, $sourceLabel, $ruleId, $matchedOn, $matchedValue) {
        return [
            'author' => $author,
            'source' => $source,
            'source_label' => $sourceLabel,
            'rule_id' => $ruleId,
            'matched_on' => $matchedOn,
            'matched_value' => $matchedValue
        ];
    }

    /**
     * 创建未知作者结果
     *
     * @return array 结果
     */
    private function createUnknownResult() {
        return $this->createResult(self::UNKNOWN_AUTHOR, 'unknown', '未识别', null, null, null);
    }
}
