module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		phpcs: {
			plugin: {
				src: './'
			},
			options: {
				bin: "vendor/bin/phpcs --extensions=php --ignore=\"*/vendor/*,*/node_modules/*\"",
				standard: "phpcs.ruleset.xml"
			}
		},

		csslint: {
			src: [ "css/admin.css" ],
			options: {
				"fallback-colors": false,              // unless we want to support IE8
				"box-sizing": false,                   // unless we want to support IE7
				"compatible-vendor-prefixes": false,   // The library on this is older than autoprefixer.
				"overqualified-elements": false,       // We have weird uses that will always generate warnings.
				"ids": false,
				"regex-selectors": false,              // audit
				"adjoining-classes": false,
				"universal-selector": false,           // audit
				"unique-headings": false,              // audit
				"outline-none": false,                 // audit
				"floats": false,
				"font-sizes": false,                   // audit
				"important": 2,
				"box-model": 2,
				"display-property-grouping": 2,
				"known-properties": 2,
				"qualified-headings": 2,
				"duplicate-background-images": 2,
				"duplicate-properties": 2,
				"star-property-hack": 2,
				"text-indent": 2,
				"shorthand": 2,
				"empty-rules": 2,
				"vendor-prefix": 2,
				"zero-units": 2
			}
		},

		postcss: {
			options: {
				map: true,
				diff: false,
				processors: [
					require( "autoprefixer" )( {
						browsers: [ "> 1%", "ie 8-11", "Firefox ESR" ]
					} )
				]
			},
			dist: {
				src: "css/admin.css",
				dest: "css/admin.post.css"
			}
		},

		cssmin: {
			combine: {
				files: {
					"css/admin.min.css": ["css/admin.post.css"]
				}
			}
		},

		clean: {
			files: ["css/admin.post.css"]
		},

		jscs: {
			src: "js/post-admin.js",
			options: {
				preset: "jquery",
				verbose: true,                                 // Display the rule name with the warning.
				requireCamelCaseOrUpperCaseIdentifiers: false, // We rely on name_name too much to change them all.
				maximumLineLength: 250,                        // temporary
				fix: false
			}
		},

		jshint: {
			src: "js/post-admin.js",
			options: {
				bitwise: true,
				curly: true,
				eqeqeq: true,
				forin: true,
				freeze: true,
				noarg: true,
				nonbsp: true,
				//quotmark: "double", // We have some fancy selectors.
				undef: true,
				unused: true,
				browser: true, // Define globals exposed by modern browsers.
				jquery: true   // Define globals exposed by jQuery.
			}
		},

		uglify: {
			build: {
				src: "js/post-admin.js",
				dest: "js/post-admin.min.js"
			}
		}
	});

	grunt.loadNpmTasks('grunt-phpcs');
	grunt.loadNpmTasks('grunt-jscs');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-csslint');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-postcss');
	grunt.loadNpmTasks('grunt-contrib-clean');

	grunt.registerTask( "css", [ "csslint", "postcss", "cssmin", "clean" ] );
	grunt.registerTask( "js", [ "jscs", "jshint", "uglify" ] );
	grunt.registerTask( "php", [ "phpcs" ] );

	grunt.registerTask( "default", [ "php", "css", "js" ] );
};
