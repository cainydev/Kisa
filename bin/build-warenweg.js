import esbuild from 'esbuild';

const isDev = process.argv.includes('--dev');

async function compile(options) {
    const context = await esbuild.context(options);

    if (isDev) {
        await context.watch();
        console.log('watching warenweg-graph …');
    } else {
        await context.rebuild();
        await context.dispose();
        console.log('built warenweg-graph');
    }
}

compile({
    define: {
        'process.env.NODE_ENV': isDev ? "'development'" : "'production'",
    },
    bundle: true,
    mainFields: ['module', 'main'],
    platform: 'neutral',
    sourcemap: isDev ? 'inline' : false,
    sourcesContent: isDev,
    treeShaking: true,
    target: ['es2020'],
    minify: !isDev,
    entryPoints: ['./resources/js/warenweg-graph.js'],
    outfile: './resources/js/dist/warenweg-graph.js',
});
