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
3. If the target you wanna backup may have many comments (user replies) in its posts, skip function `getComment` in `crawl.php`.

# Usage
1. Install and start web server, PHP, and MongoDB server locally.
2. Create your own App at [Facebook Developer](https://developers.facebook.com/).
3. Edit `config.sample.php` and save as `config.php`; edit `appId` in `crawl.html`.
4. Browse `crawl.html`, select what to crawl, submit the form, and wait.
5. Modify JavaScript in `index.html` to the id and type of what you crawled.
6. Browse `index.html` through web server to see the results.

# Disclaimer
I don't gurantee anything.
