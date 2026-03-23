const BufferShim = class Buffer extends Uint8Array {
    static from(data, encoding) {
        if (typeof data === 'string') {
            const encoder = new TextEncoder();
            return new BufferShim(encoder.encode(data));
        }
        return new BufferShim(data);
    }
    
    static alloc(size) {
        return new BufferShim(size);
    }
    
    static allocUnsafe(size) {
        return new BufferShim(size);
    }
    
    static concat(list, totalLength) {
        const length = totalLength ?? list.reduce((acc, buf) => acc + buf.length, 0);
        const result = new BufferShim(length);
        let offset = 0;
        for (const buf of list) {
            result.set(buf, offset);
            offset += buf.length;
        }
        return result;
    }
    
    static isBuffer(obj) {
        return obj instanceof BufferShim;
    }
    
    static byteLength(string) {
        return new TextEncoder().encode(string).length;
    }
    
    toString(encoding) {
        return new TextDecoder().decode(this);
    }
    
    write(string, offset, length, encoding) {
        const bytes = new TextEncoder().encode(string);
        const len = Math.min(bytes.length, this.length - offset, length ?? this.length - offset);
        this.set(bytes.subarray(0, len), offset);
        return len;
    }
    
    slice(start, end) {
        return new BufferShim(super.slice(start, end));
    }
    
    readUInt32LE(offset) {
        return this[offset] | (this[offset + 1] << 8) | (this[offset + 2] << 16) | (this[offset + 3] << 24) >>> 0;
    }
    
    writeUInt32LE(value, offset) {
        this[offset] = value & 0xff;
        this[offset + 1] = (value >> 8) & 0xff;
        this[offset + 2] = (value >> 16) & 0xff;
        this[offset + 3] = (value >> 24) & 0xff;
        return offset + 4;
    }
    
    readInt32LE(offset) {
        return this[offset] | (this[offset + 1] << 8) | (this[offset + 2] << 16) | (this[offset + 3] << 24);
    }
    
    writeInt32LE(value, offset) {
        return this.writeUInt32LE(value >>> 0, offset);
    }
};

export default BufferShim;
export const Buffer = BufferShim;
export const SlowBuffer = BufferShim;
export const INSPECT_MAX_BYTES = 50;
export const kMaxLength = 2147483647;
export const constants = {
    MAX_LENGTH: 2147483647,
    MAX_STRING_LENGTH: 536870888
};
