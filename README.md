# Wordpress No Category And Tag Base
Remove Category and Tag Base From Wordpress URLs

Removes /tag/ and /category/ fron Wordpress URLs, enabling URLs like http://example.com/technology. This plugin is based on the WP-No-Tag-Base and WP-No-Category-Base plugins. 

http://Silverscreen.in, the site for which this plugin was originally developed has over 6000 tags; and using the original plugin without modifications would have led to over 18000 redirect rules.

This plugin avoids this scenario, by adding category redirect rules first, and then adding a single rule for tag rewrite. It makes the assumption that any URL that does not fit a post, or a page or a category must be a tag.
