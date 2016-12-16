
![Pine Github cover image](https://s28.postimg.org/l73gxg45p/pine_github_cover.png)

# pine - A CLI installer for timber
A CLI tool written in PHP based on symfony console to easily create a Timber project.


![Timber Installer GIF Demo](http://gifyu.com/images/Pinetimber-installer.gif)

# Installation
```bash
composer global require azi/pine
```


__Dont't forget to add `$HOME/.composer/vendor/bin` in your path__

# Usage
```bash
pine new blog
```
This will download and install wordpress as well as timber with basic theme directory structure

## Asking for specific WordPress version
```bash
pine new blog 4.6
```
