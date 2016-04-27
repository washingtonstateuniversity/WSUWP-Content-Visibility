# WSU Content Visibility

[![Build Status](https://travis-ci.org/washingtonstateuniversity/WSUWP-Content-Visibility.svg?branch=master)](https://travis-ci.org/washingtonstateuniversity/WSUWP-Content-Visibility)

Control the visibility of content for groups of authenticated users.

## Overview

WSU Content Visibility provides a general method to control visibility of a private post based on the groups a user belongs to. The plugin does not contain any default groups. Instead, custom code should be used to provide group and user associations through the provided hooks.

For the list of groups to appear, the `post_status` of the post (or other post type) must be `private`.

## Existing capabilities in WordPress

The following conditions are true by default in WordPress:

* Subscribers, Contributors, and Authors can view their own private posts.
* Subscribers, Contributors, and Authors can not view private posts owned by others.
* Editors, Administrators, and Super Admins can view all private posts.

If WSU Content Visibility is used to assign a group of viewers to a private post that includes existing site Subscribers, Contributors, or Authors, those users will be able to view that private post.

## Post Type Support

By default, WSU Content Visibility supports posts and pages. Use `add_post_type_support( 'post-type-slug', 'wsuwp-content-viewers' );` to add support for the management of users that can **view** content.

Similarly, `remove_post_type_support()` can be used to remove existing support for a feature on a post type. Current support is applied on `init` with a priority of `10`.

## Default Groups

A default list of groups is presented once a post's status has been updated to private. The `content_visibility_default_groups` filter should be used to add or remove groups on this list.

Expected return format:

```
array(
    array( 'id' => 'unique-group-id', 'name' => 'A Group Name' ),
    array( 'id' => 'b-unique-group-id', 'name', 'B Group Name' ),
    // etc...
);
}
```

## User Verification

When an authenticated user attempts to view a private post, the `user_in_content_visibility_groups` filter fires to determine if the user is a member of an assigned group. `false` is assigned by default, but a plugin can filter this to `true`.

The user and a list of group IDs assigned as viewers to the post will be passed to the filter to help in determining view access.
