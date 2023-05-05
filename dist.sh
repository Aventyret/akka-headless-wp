echo "** package plugin files **"
rm -f akka-headless-wp.zip
rm -r -f akka-headless-wp
rm -r -f dist
cp -r plugin akka-headless-wp

echo "** zip package **"
zip -r akka-headless-wp.zip akka-headless-wp
mv akka-headless-wp dist

echo "** you have a packaged plugin at ./akka-headless-wp.zip **"
echo "** the contents of the package can be browsed in ./dist **"
