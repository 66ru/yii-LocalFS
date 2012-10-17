File system extension for Yii Framework
===========
[![Build Status](https://secure.travis-ci.org/mediasite/yii-LocalFS.png)](http://travis-ci.org/mediasite/yii-LocalFS)

Requirements:

* GD
* ImageMagick
* MP4Box (gpac package, for proper video streaming & video converting)
* ffmpeg (for video converting)

Note: currently planing to migrate to ImageMagick

Currently works only under *nix systems

ffmpeg installing instructions:

first install required (in flags) codecs + faac faad2
```
svn checkout svn://svn.ffmpeg.org/ffmpeg/trunk ffmpeg
cd ffmpeg
./configure --enable-gpl --enable-nonfree --enable-pthreads --enable-libfaac --enable-libvorbis --enable-libmp3lame --enable-libtheora --enable-libx264 --enable-libx264 --enable-libxvid --enable-x11grab
make
make install
```