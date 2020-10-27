FILENAME="$2/dependancy_kring_in_progress"
REQ_VERSION="0.1"
echo "Destination is $1"
echo "Tmp folder is $2"
touch ${FILENAME}
echo "Launch install of Kring dependancy"
echo "* KRCPA"
echo 0 > ${FILENAME}
cd $2
echo 5  > ${FILENAME}
rm -f master.zip
echo 10  > ${FILENAME}
wget https://github.com/kavod/krcpa/archive/master.zip
echo 15 > ${FILENAME}
unzip -o "master.zip"
echo 20 > ${FILENAME}
rm -f "master.zip"
echo 25 > ${FILENAME}
rm -Rf "$1/krcpa"
echo 30 > ${FILENAME}
mkdir "$1/krcpa"
echo 35 > ${FILENAME}
mv -f krcpa-master/src/* "$1/krcpa"
echo 40 > ${FILENAME}
rm -Rf krcpa-${REQ_VERSION}
echo 45 > ${FILENAME}

echo "Everything is successfully installed!"
rm ${FILENAME}
