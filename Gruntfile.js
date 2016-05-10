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
		}
	});

	grunt.loadNpmTasks('grunt-phpcs');
	grunt.loadNpmTasks('grunt-jscs');
};
