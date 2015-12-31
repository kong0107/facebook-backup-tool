# Why do I need this?
Facebook does have a "Download a copy of your Facebook data." link in [General Account Settings](https://www.facebook.com/settings?tab=account) page. But the downloaded data has the following cons:

1. No link to the page of the origin post or photo. This means it's difficult to check whether an origin post has newer comments after you download.
2. Contains only the post message, excluding comments (replies) of them.
4. Messages are in pure text. This means tags to other user/page are lost.
5. Showing user names without the link to those users. This means if the user you mentioned has changed his/her user name, you may lose him/her.
6. All posts are listed in one page. This means the file is very large. And since the page has no hashes in its HTML, it would be inconvenient to share a specified post even you upload the file to some web server.

# Warning
1. This is unfinished.
2. Private posts and photos are crawled, too. You shall check if the contents of crawled data is suitable to publish.
3. Comments and photos are auto-downloaded. These may take more than half an hour.

# Usage
需要會架網站。

1. Install and start web server with PHP, and MongoDB server locally.
2. Create your own App on [Facebook Developer](https://developers.facebook.com/).
3. Edit `config.sample.php` and save as `config.inc.php`; edit `js/fbsdk-config.js`.
4. Browse `index.php`, select what to crawl, submit the form, and wait.
5. Download the ZIP file and extract it.
6. Browse `index.html`.

## PHP Settings
For PHP to drive MongoDB, there are two extensions: old [`mongo`](https://pecl.php.net/package/mongo) and new [`mongodb`](https://pecl.php.net/package/mongodb).

Though `mongo` (which uses classes such as `MongoClient` and `MongoCollection`) is announced deprecated and not supported for PHP 7, but it's still maintained for PHP 5.3 to 5.6.
On the other hand, `mongodb` (which uses classes such as `MongoDB\Driver\Manager` and `MongoDB\Collection`) works fine with PHP 7.x, but does not support PHP older than 5.5.

Since OpenShift, in which PaaS I decide to implement [an instance of this project](http://fbbk-kong0107.rhcloud.com), has only PHP 5.3 and 5.4 to choose, I can only use the deprecated `mongo`.

# Disclaimer
I don't guarantee anything.

# Application
(Contents of this paragraph are NOT what this app can do, but what you can do by the data you crawl by this app.)
With post/photo data of the target node and their comments downloaded, you can:
1. Search if you or somebody said something on your timeline/page/group/event.
2. Check how often a user interacts with you. Listing all frequencies of all users is also possible.

# Directory Structure
* `metadata`: JSON files about structure of Facebook Graph API nodes.
* `js`:
  * `fbsdk-extend.js`: functions based on [Facebook SDK for JavaScript](https://developers.facebook.com/docs/javascript).
* `dt`: developer tool, only for development.
  * `browser.html`: Log in Facebook and then browse it by client-side JavaScript.
  * `dbBrowse.php`: Browse MongoDB database on localhost.
  * `metadata.html`: Gets what's in folder `metadata`.
  * `template.html`: a template for new page.

# Algorithm
## Crawler: DFS / Stack
1. Push what to request to the stack.
2. Pop an element from the stack.
3. Request by the information of that element, and then save the response to the database.
4. If there's a next page, then push the next page to the stack.
5. If the nodes in the response may have comments, then push `comments` edge of all nodes to the stack.
6. If the stack is not empty, then go to step 2.
7. Finish crawling.

## Concerns
* What kind of database structure to use? Especially, save comments to a collection/table separate from their targets, or as an array element field to their targets?
  * You shall concern copyright and privacy issues.
  * If using MongoDB, note that positional `$` operator does NOT support nested array. See [MongoDB Manual](https://docs.mongodb.org/manual/reference/operator/update/positional/).
* Where to save media files?
* Which fields to request? Some fields are shown in [official introspection](https://developers.facebook.com/docs/graph-api/using-graph-api#introspection) but requesting them may trigger error. Furthermore, do you want to save all returned fields or forget about some after process? (For example, as this is used for backup usage, it's a contradiction to save the URL of a media since we've already downloaded the file in case that the file is delete from Facebook.)
* Output HTML files browsable without web server (by using protocol `file://`). This means you cannot use AJAX. But AngularJS's `<script type="text/ng-template" />` may be a good solution (though it does not support `src` attribute).
