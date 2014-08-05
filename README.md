# Media Marginalia #

"Marginalia are scribbles, comments and illuminations in the margins of a book." [Wikipedia](http://en.wikipedia.org/wiki/Marginalia)

Media Marginalia is a Wordpress Plugin that takes the concept of textual marginalia and applies it to video and audio.

It provides an interface to create/read/update/delete multi-media (text/video/audio) annotations for a given media file (video/audio).


# Shot Category View #

In the directory of your theme (e.g. twentyfourteen) within the wordpress themes directory, type the following commands to symlink to the custom SHOTS category templates:

```ln -s ../../plugins/media-marginalia/category-shots.php category-shots.php```
```ln -s ../../plugins/media-marginalia/content-shot.php content-shot.php```

Every specific shot category (e.g. 0001, 0002, etc.) must have its parent set to a SHOTS (slug=shots) category to tell it to use the above symlinked templates.
