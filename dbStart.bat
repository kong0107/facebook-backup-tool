SET mypath=%~dp0
"%ProgramFiles%\MongoDB\Server\3.0\bin\mongod.exe" --dbpath "%mypath:~0,-1%\data"
