const processShim = {
    env: { NODE_ENV: 'production' },
    versions: {},
    argv: [],
    platform: 'browser',
    arch: 'wasm',
    cwd: () => '/',
    nextTick: (fn, ...args) => Promise.resolve().then(() => fn(...args)),
    on: () => {},
    once: () => {},
    emit: () => false,
    removeListener: () => {},
    listeners: () => [],
    listenerCount: () => 0,
    binding: () => { throw new Error('process.binding is not available in browser'); },
    umask: () => 0,
    hrtime: () => [0, 0],
    stdout: null,
    stderr: null,
    stdin: null
};

export default processShim;
export const process = processShim;
