FILENAME="$2/dependancy_kring_in_progress"
REQ_VERSION="0.1"
BRANCH="main"

echo "Destination is $1"
echo "Tmp folder is $2"
touch ${FILENAME}
echo "Launch install of Kring dependancy"
echo "* KRCPA"
echo 0 > ${FILENAME}
cd $2
echo 5  > ${FILENAME}
rm -f ${BRANCH}.zip
echo 10  > ${FILENAME}
wget https://github.com/kavod/krcpa/archive/${BRANCH}.zip
echo 15 > ${FILENAME}
unzip -o "${BRANCH}.zip"
echo 20 > ${FILENAME}
rm -f "${BRANCH}.zip"
echo 25 > ${FILENAME}
rm -Rf "$1/krcpa"
echo 30 > ${FILENAME}
mkdir "$1/krcpa"
echo 35 > ${FILENAME}
mv -f krcpa-${BRANCH}/src/* "$1/krcpa"
echo 40 > ${FILENAME}
rm -Rf krcpa-${REQ_VERSION}
echo 45 > ${FILENAME}

echo "Everything is successfully installed!"
rm ${FILENAME}
