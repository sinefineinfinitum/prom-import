import path from 'path';
import { fileURLToPath } from 'url';
import TerserPlugin from 'terser-webpack-plugin';
import { BundleAnalyzerPlugin } from 'webpack-bundle-analyzer';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default (env, argv) => {
	const isProduction = argv.mode === 'production';
	const analyze = env?.analyze === true;

	return {
		entry: {
			plugin: './assets/js/src/plugin.js',
		},
		output: {
			filename: '[name].min.js',
			path: path.resolve(__dirname, 'assets/js/dist'),
			clean: true,
		},
		module: {
			rules: [
				{
					test: /\.js$/,
					exclude: /node_modules/,
					use: {
						loader: 'babel-loader',
						options: {
							presets: [
								['@babel/preset-env', {
									targets: {
										browsers: ['defaults', 'not ie 11']
									},
									modules: false,
								}]
							]
						}
					}
				}
			]
		},
		optimization: {
			minimize: isProduction,
			minimizer: [
				new TerserPlugin({
					terserOptions: {
						format: {
							comments: false,
						},
						compress: {
							drop_console: isProduction,
						}
					},
					extractComments: false,
				})
			],
		},
		devtool: isProduction ? 'source-map' : 'eval-source-map',
		externals: {
			jquery: 'jQuery',
		},
		plugins: [
			...(analyze ? [new BundleAnalyzerPlugin()] : [])
		],
		stats: {
			colors: true,
			hash: false,
			version: false,
			timings: true,
			assets: true,
			chunks: false,
			modules: false,
			children: false,
		}
	};
};
