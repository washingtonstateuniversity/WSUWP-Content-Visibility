module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		jscs: {
			src: [ "js/models/*.js", "js/views/*.js", "js/content-visibility-app.js" ],
			options: {
				preset: "jquery",
				verbose: true,                                 // Display the rule name with the warning.
				requireCamelCaseOrUpperCaseIdentifiers: false, // We rely on name_name too much to change them all.
				maximumLineLength: 150,                        // temporary
			}
		},

		concat: {
			ad_visibility: {
				src: [
					'js/views/content-visibility-group.js',
					'js/views/content-visibility-app.js',
					'js/models/content-visibility-group.js',
					'js/content-visibility-app.js'
				],
				dest: 'js/content-visibility.js'
			}
		},

		cssmin: {
			combine: {
				files: {
					"css/admin-style.min.css": ["css/admin-style.css"]
				}
			}
		},

		uglify: {
			theme: {
				expand: true,
				cwd: 'js/',
				dest: 'js/',
				ext: '.min.js',
				src: [
					'content-visibility.js'
				]
			}
		},

		clean: {
			temporary: {
				src: [
					'js/content-visibility.js'
				]
			}
		},

		watch: {
			files: [
				'js/views/content-visibility-group.js',
				'js/views/content-visibility-app.js',
				'js/models/content-visibility-group.js',
				'js/content-visibility-app.js'
			],
			tasks: ['default']
		},

		phpcs: {
			plugin: {
				src: './'
			},
			options: {
				bin: "vendor/bin/phpcs --extensions=php --ignore=\"*/vendor/*,*/node_modules/*\"",
				standard: "phpcs.ruleset.xml"
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-jscs');
	grunt.loadNpmTasks('grunt-phpcs');

	// Default task(s).
	grunt.registerTask('default', ['concat', 'uglify', 'clean', 'cssmin']);
};
