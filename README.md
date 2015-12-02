# Why do I need this?
Facebook does have a "Download a copy of your Facebook data." link in [General Account Settings](https://www.facebook.com/settings?tab=account) page. But the downloaded data has the following cons:

1. No link to the page of the origin post or photo. This means it's difficult to check whether an origin post has newer comments after you download.
2. Contains only the post message, excluding comments (replies) of them.
4. Messages are in pure text. This means tags to other user/page are lost.
5. Showing user names without the link to those users. This means if the user you mentioned has changed his/her user name, you may lose him/her.
6. All posts are listed in one page. This means the file is very large. And since the page has no hashes in its HTML, it would be inconvenient to share a specified post even you upload the file to some web server.

# Warning
This is unfinished.

# Usage
1. Install and start web server, PHP, and MongoDB server locally.
2. Browse `crawl.html`, select what to crawl, submit the form, and wait.
3. Modify JavaScript in `index.html` to the id and type of what you crawled.
4. Browse `index.html` through web server to see the results.
