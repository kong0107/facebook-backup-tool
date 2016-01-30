# Status
其實支援下載使用者自己的資料，不過臉書 App 審核沒過，因此程式碼預設只能下載粉絲專頁。
如果要下載自己的資料：

1. 把程式碼放在靜態網頁伺服器（最簡單的方式就是在 GitHub 上複製成新專案的 [gh-pages 分支](https://help.github.com/articles/creating-project-pages-manually/)）
2. 到[臉書開發人員](https://developers.facebook.com/)介面新增一個自己的 App 。
3. 修改程式碼： `js/fbsdk-config.js` 中的 `appId` ，以及 `js/index.js` 中的 `$scope.disableUserCrawl` 。（我知道程式碼很亂，但我現在不想整理）
4. 用 HTTP(S) 協定去瀏覽網頁，應該就會有 "My data" 的按鈕可以按。

This project can download your own data. But I didn't pass Facebook App review. Therefore the site can only download data of fan pages.
To download your own Facebook data:

1. Copy the codes of this project to a (static) web server, such as GitHub Pages.
2. Go to [Facebook Developers](https://developers.facebook.com/) to add a new app.
3. Modify:
  * `js/fbsdk-config.js`: `appId`
  * `js/index.js`: `$scope.disableUserCrawl`
4. Browse via HTTP(S) protocol. There suppose to be a "My data" button.

PS: I know the codes are messy, but I just don't wanna rearrange them now.

# Why do I need this?
Facebook does have a "Download a copy of your Facebook data." link in [General Account Settings](https://www.facebook.com/settings?tab=account) page. But the downloaded data has the following cons:

1. No link to the page of the origin post or photo. This means it's difficult to check whether an origin post has newer comments after you download.
2. Contains only the post message, excluding comments (replies) of them.
4. Messages are in pure text. This means tags to other user/page are lost.
5. Showing user names without the link to those users. This means if the user you mentioned has changed his/her user name, you may lose him/her.
6. All posts are listed in one page. This means the file is very large. And since the page has no hashes in its HTML, it would be inconvenient to share a specified post even you upload the file to some web server.

# Warning
Private posts and photos are crawled, too. You shall check if the contents of crawled data is suitable to publish.

# Usage
https://kong0107.github.io/facebook-backup/

# Disclaimer
I don't guarantee anything.
