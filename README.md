# WSU Content Visibility

[![Build Status](https://travis-ci.org/washingtonstateuniversity/WSUWP-Content-Visibility.svg?branch=master)](https://travis-ci.org/washingtonstateuniversity/WSUWP-Content-Visibility)

Control the visibility of content for groups of authenticated users.

## Overview

WSU Content Visibility provides a general method to control visibility of a private post based on the groups a user belongs to. The plugin does not contain any default groups. Instead, custom code should be used to provide group and user associations through the provided hooks.

* The `post_status` of the post (or other post type) must be `private`.
* Post authors are always able to view their own posts.
* Hooks are available to provide groups and associate users with those groups.

## Post Type Support

There are two levels of support to add for post types.

* Use `add_post_type_support( 'post-type-slug', 'wsuwp-content-viewers' );` to add support for the management of users that can **view** content.
* Use `add_post_type_support( 'post-type-slug', 'wsuwp-content-editors' );` to add support for the management of users that can **edit** content.

Similarly, `remove_post_type_support()` can be used to remove existing support for a feature on a post type. We apply support on `init` with a priority of `10`.

## Group Search

When a search term is entered through the admin interface and submitted to the server, the `content_visibility_group_search` filter is available to filter the group results list. Data must be formatted as such:

```
$groups = array(
	array(
		'id' => 'unique-group-id',
		'display_name' => 'Group Display Name',
		'member_count' => 5,
		'member_list' => array(
			'user_one',
			'user_two',
			'user_three',
			'user_four',
			'user_five',
		),
	), array(
	   // etc...
	)
);
```

This list of results will then be presented to the post author by WSU Content Visibility to assign to the post.

## User Verification

When an authenticated user attempts to view a private post, the `user_in_content_visibility_groups` filter fires to determine if the user is a member of an assigned group. `false` is assigned by default, but a plugin can filter this to `true`.

## Group Details

At this time, only group IDs are stored as meta on protected posts. When displaying the groups currently assigned to a post, `content_visibility_group_details` is fired to allow a plugin to populate the rest of the details of the group either from cache or a new lookup.
