SET mypath=%~dp0
"%ProgramFiles%\MongoDB\Server\3.2\bin\mongod.exe" --dbpath "%mypath:~0,-1%\data"
