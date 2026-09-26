#!/usr/bin/env bash
# Resize a photo for the theme and save it as WebP in
# wp-content/themes/eares/assets/images/<name>.webp. Uses PHP's GD in the
# wordpress container, so nothing needs installing on the host.
#
#   scripts/optimize-photo.sh assets/pictures/NX3_9750.JPG.jpg dome 2400
source "$(dirname "$0")/lib.sh"

if (( $# < 2 )); then
  echo "Usage: $0 <photo> <name> [width, default 1600]" >&2
  exit 1
fi
src="$1" name="$2" width="${3:-1600}"
out="wp-content/themes/eares/assets/images/$name.webp"

# shellcheck disable=SC2016 # PHP code, not shell.
# A 24-megapixel decode needs well over the default 128M.
docker compose exec -T -e WIDTH="$width" wordpress php -d memory_limit=1G -r '
$data = file_get_contents( "php://stdin" );
$img  = imagecreatefromstring( $data );
$tmp  = tempnam( sys_get_temp_dir(), "photo" );
file_put_contents( $tmp, $data );
$exif = @exif_read_data( $tmp );
unlink( $tmp );
$rotate = array( 3 => 180, 6 => -90, 8 => 90 )[ $exif["Orientation"] ?? 1 ] ?? 0;
if ( $rotate ) {
	$img = imagerotate( $img, $rotate, 0 );
}
$width = min( (int) getenv( "WIDTH" ), imagesx( $img ) );
imagewebp( imagescale( $img, $width, -1, IMG_BICUBIC ), "php://stdout", 78 );
' < "$src" > "$out"

echo "$out: $(du -h "$out" | cut -f1)"
