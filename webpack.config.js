/* eslint-env es6 */

const webpack = require('webpack')
const path = require('path')
const yaml = require('yamljs')
const fs = require('fs')
const _ = require('lodash')
const mkdirp = require('mkdirp');
const deepMerge = require('merge-objects');
const ContextReplacementPlugin = require('webpack/lib/ContextReplacementPlugin')
const pathOverrides = require('./frontend/path-overrides')
const requireConfigPaths = require('./web/js/require-config')

const getRelativePaths = (absolutePaths) => {
    const replacedPaths = {}
    for (let absolutePath in absolutePaths) {
        const pathValue = absolutePaths[absolutePath]
        replacedPaths[pathValue] = pathValue.replace(__dirname + '/src', './')
    }

    return replacedPaths
}

const getAbsolutePaths = (relativePaths, configPath) => {
    const absolutePaths = {}

    for (let relativePathName in relativePaths) {
        let relativePath = relativePaths[relativePathName].split('/')
        relativePath.shift()
        const resourcePath = path.resolve(configPath, '../../')
        absolutePaths[relativePathName] = resourcePath + '/public/' + relativePath.join('/')
    }

    return absolutePaths
}

const getRequireConfig = (requirePaths) => {
    let modulePaths = {}
    let config = {}

    requirePaths.forEach((requirePath) => {
        try {
            const contents = fs.readFileSync(requirePath, 'utf8')
            const parsedFile = yaml.parse(contents)
            const bundlePaths = parsedFile.config.paths
            const bundleConfig = parsedFile.config.config
            const absolutePaths = getAbsolutePaths(bundlePaths, requirePath)
            modulePaths = deepMerge(modulePaths, absolutePaths)
            config = deepMerge(config, bundleConfig)
        } catch (e) { }
    })

    return {
        config,
        modulePaths
    }
}

const requireConfig = getRequireConfig(requireConfigPaths)
const importedPaths = requireConfig.modulePaths
const generalConfig = requireConfig.config
const overrides = _.mapValues(pathOverrides, override => path.resolve(override))
const importPaths = Object.assign(importedPaths, overrides)


mkdirp('web/dist', function () {
    fs.writeFileSync('web/dist/general.js', `module.exports = ${JSON.stringify(generalConfig)}`, 'utf8')

    fs.writeFileSync('web/dist/paths.js', `module.exports = ${JSON.stringify(importPaths)}`, 'utf8')
});

module.exports = {
    target: 'web',
    entry: './src/Pim/Bundle/EnrichBundle/Resources/public/js/index.js',
    output: {
        path: path.resolve(__dirname, './web/dist/'),
        publicPath: 'dist/',
        filename: 'app.min.js',
        chunkFilename: '[name].bundle.js',
        pathinfo: true,
        devtoolLineToLine: true
    },
    resolve: {
        alias: importPaths
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                use: [{
                    loader: path.resolve('./config-loader'),
                    options: { }
                }]
            },
            {
                test: /\.html$/,
                use: [{
                    loader: 'raw-loader',
                    options: { }
                }]
            },
            {
                test: path.resolve(__dirname, './src/Pim/Bundle/UIBundle/Resources/public/lib/backbone/backbone.js'),
                use: 'imports-loader?this=>window'
            },
            {
                test: path.resolve(__dirname, './src/Pim/Bundle/UIBundle/Resources/public/lib/jquery/jquery-1.10.2'),
                use: [
                    {
                        loader: 'expose-loader',
                        options: 'jQuery'
                    },
                    {
                        loader: 'expose-loader',
                        options: '$'
                    }
                ]
            },
            {
                test: path.resolve(__dirname, './src/Pim/Bundle/EnrichBundle/Resources/public/js/app'),
                use: [{
                    loader: 'expose-loader',
                    options: 'PimApp'
                }]
            },
            {
                test: path.resolve(__dirname, './frontend/require-polyfill.js'),
                use: [{
                    loader: 'expose-loader',
                    options: 'require'
                }]
            }

        ]
    },
    resolveLoader: {
        moduleExtensions: ['-loader']
    },
    plugins: [
        new webpack.ProvidePlugin({
            '_': 'underscore',
            'Backbone': 'backbone',
            '$': 'jquery'
        }),
        // This is needed until summernote is updated
        new webpack.DefinePlugin({
            'require.specified': 'require.resolve'
        }),
        new ContextReplacementPlugin(
          /src/,
          path.resolve(__dirname, './src'),
          getRelativePaths(importPaths)
        )
    ]
}
