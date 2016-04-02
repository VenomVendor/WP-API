# release.sh

TMPDIR=dist
REST_DIR=rest-api
REST_CUS_DIR=rest_api_customised

if [ -d "$TMPDIR" ]; then
    # Wipe it clean
    rm -r "$TMPDIR"
fi

# Ensure the directory exists first
mkdir "$TMPDIR"

if [ -d "$TMPDIR/$REST_DIR" ]; then
    # Wipe it clean
    rm -r "$TMPDIR/$REST_DIR"
fi

# Ensure the directory exists first
mkdir "$TMPDIR/$REST_DIR"

cp -r $REST_CUS_DIR $TMPDIR
cp -r lib $TMPDIR/$REST_DIR

cp CHANGELOG.md $TMPDIR/$REST_DIR
cp CONTRIBUTING.md $TMPDIR/$REST_DIR
cp README.md $TMPDIR/$REST_DIR
cp core-integration.php $TMPDIR/$REST_DIR
cp extras.php $TMPDIR/$REST_DIR
cp license.txt $TMPDIR/$REST_DIR
cp plugin.php $TMPDIR/$REST_DIR
cp bin/readme.txt $TMPDIR/$REST_DIR
cp wp-api.js $TMPDIR/$REST_DIR
cp wp-api.min.js $TMPDIR/$REST_DIR
cp wp-api.min.map $TMPDIR/$REST_DIR

cd $TMPDIR

zip -r $REST_CUS_DIR.zip $REST_CUS_DIR
zip -r $REST_DIR.zip $REST_DIR

rm -r "$REST_DIR"
rm -r "$REST_CUS_DIR"

cd ..
