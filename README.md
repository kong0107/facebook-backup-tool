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
1. Install and start web server, PHP, and MongoDB server locally.
2. Create your own App at [Facebook Developer](https://developers.facebook.com/).
3. Edit `config.sample.php` and save as `config.inc.php`; edit `js/fbsdk-config.js`.
4. Browse `index.php`, select what to crawl, submit the form, and wait. Download the JSON files.
5. Modify JavaScript in `index.html` to the id and type of what you crawled.
6. Browse `index.html` through web server to see the results.

# Disclaimer
I don't guarantee anything.

# Application
(Contents of this paragraph are NOT what this app can do, but what you can do by the data you crawl by this app.)
With post/photo data of the target node and their comments downloaded, you can:
1. Search if you or somebody said something on your timeline/page/group/event.
2. Check how often a user interacts with you. Listing all frequencies of all users is also possible.

# Directory Structure
* `templates`: HTML templates for AngularJS.
* `metadata`: JSON files about structure of Facebook Graph API nodes.
* `js`:
  * `fbsdk-extend.js`: functions based on [Facebook SDK for JavaScript](https://developers.facebook.com/docs/javascript).
* `dt`: developer tool, only for development.
  * `browser.html`: Log in Facebook and then browse it by client-side JavaScript. (No server needed)
  * `dbBrowse.php`: Browse MongoDB database on localhost.
  * `metadata.html`: Gets what's in folder `metadata`.
  * `template.html`: a template for new page.

# Algorithm
## Crawler: DFS / Stack
0. Push what to request to the stack.
1. Pop an element from the stack.
2. Request the data and save it to the database.
3. If it's a node, then push its edges to the stack.
4. If it's an edge and there's next page, then push the next page.
5. If it's an edge whose nodes may have comments, then push `comments` edge of all nodes (in the current page) to the stack.
6. If the stack is not empty, then go to step 1.
7. Finish crawling.

## Concerns
* What kind of database structure to use? Especially, save comments to a collection/table separate from their targets, or as an array element field to their targets?
  * You shall concern copyright and privacy issues.
  * If using MongoDB, note that positional `$` operator does NOT support nested array. See [MongoDB Manual](https://docs.mongodb.org/manual/reference/operator/update/positional/).
* Where to save media files?
* Which fields to request? Some fields are shown in [official introspection](https://developers.facebook.com/docs/graph-api/using-graph-api#introspection) but requesting them may trigger error. Furthermore, do you want to save all returned fields or forget about some after process? (For example, as this is used for backup usage, it's a contradiction to save the URL of a media since we've already downloaded the file in case that the file is delete from Facebook.)
