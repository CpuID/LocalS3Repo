## LocalS3Repo - MediaWiki Extension for storing File Uploads/Images on S3

LocalS3Repo modified to work with MediaWiki 1.26.x, CloudFront CDN, and rotating IAM keys.
Modified further to support both IAM keys and static auth (was hardcoded to IAM keys only in some places)

Based on
* https://github.com/cariaso/LocalS3Repo
* https://github.com/oborseth/LocalS3Repo2
* https://www.mediawiki.org/wiki/Extension:LocalS3Repo
	
## Moving from local files to S3

A maintenance script that was used to move current images between s3 buckets is included.
It will probably need to be slightly modified to work for you.

Due to the fiddlyness of the migration script, consider using awscli to just sync the images dir up, example:

```
aws s3 sync images/ s3://bucketname/
```

## Settings

Modify the below as required and put them in your LocalSettings.php:

$wgUploadDirectory is the directory in your bucket where the image directories and images will be stored.

If "images" doesn't work for you, change it.

```php
$wgUploadDirectory = 'images';
$wgUploadS3Bucket = 'YOUR S3 BUCKET';
$wgUploadS3SSL = false; // true if SSL should be used
$wgPublicS3 = true; // true if public, false if authentication should be used

$wgS3BaseUrl = "http".($wgUploadS3SSL?"s":"")."://s3.amazonaws.com/$wgUploadS3Bucket";

//viewing needs a different url from uploading. Uploading doesnt work on the below url and viewing doesnt work on the above one.
$wgS3BaseUrlView = "http".($wgUploadS3SSL?"s":"")."://".$wgUploadS3Bucket.".s3.amazonaws.com";
$wgUploadBaseUrl = "$wgS3BaseUrl/$wgUploadDirectory";

// leave $wgCloudFrontUrl blank to not render images from CloudFront
// $wgCloudFrontUrl = "http".($wgUploadS3SSL?"s":"").'://YOUR_CLOUDFRONT_SUBDOMAIN.cloudfront.net/';

$wgLocalFileRepo = array(
        'class' => 'LocalS3Repo',
        'name' => 's3',
        'directory' => $wgUploadDirectory,
        'url' => $wgUploadBaseUrl ? $wgUploadBaseUrl . $wgUploadPath : $wgUploadPath,
        'urlbase' => $wgS3BaseUrl ? $wgS3BaseUrl : "",
        'hashLevels' => $wgHashedUploadDirectory ? 2 : 0,
        'thumbScriptUrl' => $wgThumbnailScriptPath,
        'transformVia404' => !$wgGenerateThumbnailOnParse,
        'initialCapital' => $wgCapitalLinks,
        'deletedDir' => $wgUploadDirectory.'/deleted',
        // If you want to use IAM Instance Profile Auth, set these both to null.
        'AWS_ACCESS_KEY' => $auth['AccessKeyId'],
        'AWS_SECRET_KEY' => $auth['SecretAccessKey'],
        'AWS_S3_BUCKET' => $wgUploadS3Bucket,
        'AWS_S3_PUBLIC' => $wgPublicS3,
        'AWS_S3_SSL' => $wgUploadS3SSL,
        'cloudFrontUrl' => $wgCloudFrontUrl,
);
require_once("$IP/extensions/LocalS3Repo/LocalS3Repo.php");
```
