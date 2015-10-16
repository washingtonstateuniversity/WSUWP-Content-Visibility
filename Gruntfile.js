module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

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
		}
	});

	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-watch');

	// Default task(s).
	grunt.registerTask('default', ['concat', 'uglify', 'clean', 'cssmin']);
};
