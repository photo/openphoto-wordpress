#! /bin/bash
#
# Script to deploy from Github to WordPress.org Plugin Repository
# A modification of Dean Clatworthy's deploy script as found here: https://github.com/deanc/wordpress-plugin-git-svn
# The difference is that this script lives in the plugin's git repo & doesn't require an existing SVN repo.
# Source: https://github.com/thenbrent/multisite-user-management/blob/master/deploy.sh

# Configure these values for each plugin.
GITSLUG="openphoto-wordpress"
MAINFILE="openphoto-wordpress.php"
SVNSLUG="openphoto"
SVNUSER="randyhoyt"

# ###### Do not modify below this point. ######

# Set up Git repository configuration.
GITPATH=`pwd`
GITFOLDER='plugins/'$GITSLUG

# Prompt for the new version number
echo "What is the new version number?"
read NEWVERSION

# Update version number, tag the version, push everything to master.
echo "Tagging new version in Git."
CURRENTVERSION=`grep "^Stable tag:" $GITPATH/readme.txt | awk -F' ' '{print $NF}'`
sed -c -i 's/Stable tag: '$CURRENTVERSION'/Stable tag: '${NEWVERSION}'/g' ${GITPATH}/readme.txt
CURRENTVERSION=`grep "^Version:" $GITPATH/$MAINFILE | awk -F' ' '{print $NF}'`
sed -c -i 's/Version: '$CURRENTVERSION'/Version: '${NEWVERSION}'/g' ${GITPATH}/${MAINFILE}
git add readme.txt
git add ${GITPATH}/${MAINFILE}
git commit -m "Tagging version $NEWVERSION"
git tag -a "$NEWVERSION" -m "Tagging version $NEWVERSION"
git checkout master
git merge dev
git add *
git commit -m "Merging version $NEWVERSION to master"
git push
git push --tags
git checkout dev
<<<<<<< HEAD
NEWVERSION=`grep "^Stable tag:" $GITPATH/readme.txt | awk -F' ' '{print $NF}'`
<<<<<<< HEAD
<<<<<<< HEAD
sed -c -i 's/Stable tag: '$NEWVERSION'/Stable tag: 'VERSION_NUMBER/g' ${GITPATH}/readme.txt
NEWVERSION=`grep "^Version:" $GITPATH/$MAINFILE | awk -F' ' '{print $NF}'`
sed -c -i 's/Version: '$NEWVERSION'/Version: 'VERSION_NUMBER/g' ${GITPATH}/${MAINFILE}
=======
sed -c -i 's/Stable tag: '$NEWVERSION'/Stable tag: %VERSION_NUMBER%/g' ${GITPATH}/readme.txt
NEWVERSION=`grep "^Version:" $GITPATH/$MAINFILE | awk -F' ' '{print $NF}'`
sed -c -i 's/Version: '$NEWVERSION'/Version: %VERSION_NUMBER%/g' ${GITPATH}/${MAINFILE}
>>>>>>> dev
=======
sed -c -i 's/Stable tag: '$NEWVERSION'/Stable tag: VERSION_NUMBER/g' ${GITPATH}/readme.txt
NEWVERSION=`grep "^Version:" $GITPATH/$MAINFILE | awk -F' ' '{print $NF}'`
sed -c -i 's/Version: '$NEWVERSION'/Version: VERSION_NUMBER/g' ${GITPATH}/${MAINFILE}
>>>>>>> dev
git add readme.txt
git add ${GITPATH}/${MAINFILE}
git commit -m "Tagging version $NEWVERSION"
git push
=======
>>>>>>> dev

# Set up Subversion repository configuration.
SVNFOLDER='svn/'$SVNSLUG
SVNPATH=${GITPATH/$GITFOLDER/$SVNFOLDER}
SVNURL="http://plugins.svn.wordpress.org/$SVNSLUG"

# Remove any folders in the current Subversion folder and checkout the repository afresh.
rm -r -f $SVNPATH

echo "Creating local copy of the Subversion repository."
svn co $SVNURL $SVNPATH

echo "Ignoring github specific files and deployment script."
svn propset svn:ignore "deploy.sh
README.md
.git
.gitignore" "$SVNPATH/trunk/"

echo "Exporting from Git to Subversion trunk."
git checkout-index -a -f --prefix=$SVNPATH/trunk/



echo "Switching to Subversion directory and committing."
cd $SVNPATH/trunk/
svn commit --username=$SVNUSER -m "Committing version $NEWVERSIONTXT"


cd $GITPATH
NEWVERSION=`grep "^Stable tag:" $GITPATH/readme.txt | awk -F' ' '{print $NF}'`
sed -c -i 's/Stable tag: '$NEWVERSION'/Stable tag: %VERSION_NUMBER%/g' ${GITPATH}/readme.txt
NEWVERSION=`grep "^Version:" $GITPATH/$MAINFILE | awk -F' ' '{print $NF}'`
sed -c -i 's/Version: '$NEWVERSION'/Version: %VERSION_NUMBER%/g' ${GITPATH}/${MAINFILE}
git add readme.txt
git add ${GITPATH}/${MAINFILE}
git commit -m "Tagging version $NEWVERSION"
git push