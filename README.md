## PixivSpider
This is a spider to get artworks from Pixiv recommended images,inspired by [PixivforMuzei3](https://github.com/yellowbluesky/PixivforMuzei3).

## Feature
* Bypass SNI
* Filter Support

## Example config
`hosts.json`:
```json
{
    "api": [
        "210.140.131.223"
    ],
    "img": [
        "210.140.92.147"
    ]
}
```
`config.json`:
```json
{
    "SavePath": "./background",
    "MaxRetry": 10,
    "MaxCountPreDownload": 10,
    "RemoveOldArtworks": false,
    "filter": {
        "minViewed": 0,
        "minMarked": 0,
        "allowOrientation": ["landscape","portrait"],
        "minWidth": 0,
        "minHeight": 0,
        "allowType": ["illust","manga"],
        "allowSFWLevel": ["SFW","BitNSFW","VeryNSFW","R18"],
        "ignoreMarked": false,
        "ignoreFollowedUser": false,
        "blockTags": [],
        "blockUsers": []
    }
}
```
`login.json`:
```json
{
    "username": "username",
    "passwd": "12345687"
}
```