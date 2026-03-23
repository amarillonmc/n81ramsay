export default {};
export const readFileSync = () => { throw new Error('fs.readFileSync is not available in browser'); };
export const readdirSync = () => [];
export const existsSync = () => false;
export const statSync = () => ({ isFile: () => false, isDirectory: () => false });
export const readFile = () => Promise.reject(new Error('fs.readFile is not available in browser'));
export const readdir = () => Promise.resolve([]);
export const promises = {
    readFile: () => Promise.reject(new Error('fs.promises.readFile is not available in browser')),
    readdir: () => Promise.resolve([]),
    stat: () => Promise.resolve({ isFile: () => false, isDirectory: () => false })
};
