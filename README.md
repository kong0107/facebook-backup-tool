# Why do I need this?
Facebook does have a "Download a copy of your Facebook data." link in [General Account Settings](https://www.facebook.com/settings?tab=account) page. But the downloaded data has the following cons:

1. No link to the page of the origin post or photo. This means it's difficult to check whether an origin post has newer comments after you download.
2. Contains only the post message, excluding comments (replies) of them.
4. Messages are in pure text. This means tags to other user/page are lost.
5. Showing user names without the link to those users. This means if the user you mentioned has changed his/her user name, you may lose him/her.
6. All posts are listed in one page. This means the file is very large. And since the page has no hashes in its HTML, it would be inconvenient to share a specified post even you upload the file to some web server.

# Warning
This is unfinished.

# Algorithm
1. Decide to use `/user/feed` or `/user/posts`. The former would show posts that this person was tagged in. [ref](https://developers.facebook.com/docs/graph-api/reference/v2.5/user/feed)
2. Request the first page.

```javascript
@see https://blog.jcoglan.com/2010/08/30/the-potentially-asynchronous-loop/
var path = 'me/posts';
var a = function() {
  FB.apiwt(path, function(r) {
    path = r.paging.previous;
    r.data.asyncEach(
      function(post, resume) {
        /// ...
        /// check if there are more comments.
        /// ...
        ajax.post('createPost.php', post, resume);
      },
      a ///< To support this argument, modify `asyncEach` to have a callback function executed when the iteration ends.
    );
  });
};

a();
```
