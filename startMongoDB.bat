SET mypath=%~dp0
mongod --dbpath "%mypath:~0,-1%\data"
