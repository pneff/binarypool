#!/bin/sh
#
# Run the unit tests and print output to file system.

dir=$(dirname $0)
cd $dir
mkdir -p result
rm -f result/*.xml

echo "Running unit tests..."
php test.php -suite=unit | tee result/unit.xml
