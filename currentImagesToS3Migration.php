<?php
/*
	This script can be used to upload existing images to S3. You'll need to
	copy it into your MediaWiki maintenance directory and run it from there.
	You'll probably also need to modify it, see comments below.

        Owen Borseth - owen at borseth dot us
 
  Modified further again for my usage (Nathan Sullivan)
 */

require_once( dirname( __FILE__ ) . "/Maintenance.php" );

class ImageMigration extends Maintenance {
	var $AWS_ACCESS_KEY, $AWS_SECRET_KEY, $AWS_S3_BUCKET, $AWS_S3_PUBLIC, $AWS_S3_SSL;

	public function execute() {
		$this->AWS_ACCESS_KEY = 'YOUR_AWS_ACCESS_KEY';
		$this->AWS_SECRET_KEY = 'YOUR_AWS_SECRET_KEY';
		$this->AWS_S3_BUCKET = 'YOUR_AWS_S3_BUCKET';
		$this->AWS_S3_PUBLIC = false;
		$this->AWS_S3_SSL = true;

		$s3 = new S3($this->AWS_ACCESS_KEY, $this->AWS_SECRET_KEY, $this->AWS_S3_SSL);

		// One scenario is that the images are in N different S3 buckets already. It will search these locations to try and find it.
		// If your files are local, this section is ignored based on changes further down (approx line 63 onwards).
		// $s3Buckets = array("");

		$dbw = wfGetDB( DB_MASTER );

		$counter = 0;
		$iIncrement = 10000;
		for($i = 0; ; $i += $iIncrement) {
			$res = $dbw->select(array('image', 'imagelinks', 'page'), array('image.img_name','image.img_path', 'page.page_title'), 'image.img_name = imagelinks.il_to and imagelinks.il_from = page.page_id and page.page_namespace = 0 limit '.$i.', '.$iIncrement, array());
		
			if(!$res) {
				echo('No for rows.\n');
				exit;
			}

			$logoPath = '';
			foreach($res as $row) {
				echo("counter:$counter\n");
				echo("i:$i\n");
				++$counter;
				if(!$row->img_name || !$row->img_path) {
					continue;
				}

				echo('img_name:'.$row->img_name."\n");
				echo('img_path:'.$row->img_path."\n");
				echo('page_title:'.$row->page_title."\n");
				$file = wfFindFile($row->img_name, array());
				if($file) {
					$path = $file->getFullUrl();
					$path = str_replace('http://s3.amazonaws.com/'.$this->AWS_S3_BUCKET.'/', '', $path);

					echo("path:$path\n");

					//
					// For files stored in other S3 buckets - use the below logic.
					/*
					foreach($s3Buckets as $s3Bucket) {
						if($s3->copyObject($s3Bucket, $row->img_path, $this->AWS_S3_BUCKET, $path, ($this->AWS_S3_PUBLIC ? S3::ACL_PUBLIC_READ : S3::ACL_PRIVATE)))
							echo('SUCCESS:'.$row->img_name."\n");
							break;
						} else {
							echo('ERROR1:'.$row->img_name."\n");
						}
					}
					*/
					//
					// For files stored locally in images/ - use the below logic.
					if($s3->putObject($row->img_name, $this->AWS_S3_BUCKET, $path, ($this->AWS_S3_PUBLIC ? S3::ACL_PUBLIC_READ : S3::ACL_PRIVATE))) {
						echo('SUCCESS:'.$row->img_name."\n");
						break;
					} else {
						echo('ERROR1:'.$row->img_name."\n");
					}
				} else {
					echo('ERROR2:'.$row->img_name."\n");
				}
				echo("\n");
			}
		}
	}
}

$maintClass = 'ImageMigration';
if( defined('RUN_MAINTENANCE_IF_MAIN') ) {
	require_once( RUN_MAINTENANCE_IF_MAIN );
} else {
	require_once( DO_MAINTENANCE ); # Make this work on versions before 1.17
}
