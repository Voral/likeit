#!/bin/sh
cd versions
rm $1.tar.gz
tar -zcvf $1.tar.gz $1/
cd ..
