
![Pine Github cover image](https://s18.postimg.org/xpextxkm1/pine-cover-centered.png)

# pine - A CLI installer for timber
A CLI tool written in PHP based on symfony console to easily create a Timber project.


![Pine Animated Demo](https://gifyu.com/images/pine-animation.gif)

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

## Options
| Option         | Description                                                                                                                                             | Default Value | Required |
|----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------|---------------|----------|
| --prefix       | The database table prefix, By default pine uses the project name as prefix for example  If you run `pine new blog` the table prefix will becose `blog_` | Project name  | No       |
| --skip-install | If passed pine will skip the WordPress installation step.                                                                                               | -             | No       |
| --npm          | If passed pine will run `npm install` in the newly generated theme.                                                                                     | -             | NO       |
