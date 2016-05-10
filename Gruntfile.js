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

		jscs: {
			src: "js/*.js",
			options: {
				preset: "jquery",
				verbose: true,                                 // Display the rule name with the warning.
				requireCamelCaseOrUpperCaseIdentifiers: false, // We rely on name_name too much to change them all.
				maximumLineLength: 250,                        // temporary
				fix: false
			}
		},

		jshint: {
			src: "js/*.js",
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
		}
	});

	grunt.loadNpmTasks('grunt-phpcs');
	grunt.loadNpmTasks('grunt-jscs');
	grunt.loadNpmTasks('grunt-contrib-jshint');
};
