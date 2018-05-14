Format: 3.0 (quilt)
Source: cdebconf
Binary: cdebconf, cdebconf-gtk, libdebconfclient0, libdebconfclient0-dev, cdebconf-udeb, cdebconf-priority, libdebconfclient0-udeb, cdebconf-text-udeb, cdebconf-newt-udeb, cdebconf-gtk-udeb
Architecture: any all
Version: 0.227-1arcadia1
Maintainer: Debian Install System Team <debian-boot@lists.debian.org>
Uploaders:  Colin Watson <cjwatson@debian.org>, Christian Perrier <bubulle@debian.org>, Regis Boudin <regis@debian.org>, Cyril Brulebois <kibi@debian.org>
Standards-Version: 3.9.7
Vcs-Browser: https://anonscm.debian.org/cgit/d-i/cdebconf.git
Vcs-Git: https://anonscm.debian.org/git/d-i/cdebconf.git
Build-Depends: debhelper (>= 9), po-debconf (>= 0.5.0), libslang2-dev, libnewt-dev, libtextwrap-dev (>= 0.1-5), libdebian-installer4-dev (>= 0.41) | libdebian-installer-dev, libglib2.0-dev (>= 2.31), libgtk2.0-dev (>= 2.24), libcairo2-dev (>= 1.8.10-3), libselinux1-dev (>= 2.3) [linux-any] | libselinux-dev [linux-any], dh-autoreconf, dh-exec
Package-List:
 cdebconf deb utils extra arch=any
 cdebconf-gtk deb admin extra arch=any
 cdebconf-gtk-udeb udeb debian-installer optional arch=any
 cdebconf-newt-udeb udeb debian-installer optional arch=any
 cdebconf-priority udeb debian-installer standard arch=all
 cdebconf-text-udeb udeb debian-installer optional arch=any
 cdebconf-udeb udeb debian-installer standard arch=any
 libdebconfclient0 deb libs optional arch=any
 libdebconfclient0-dev deb libdevel optional arch=any
 libdebconfclient0-udeb udeb debian-installer optional arch=any
Checksums-Sha1:
 9d8118b3f122403c5b0f50a5089fd15b57ac8b0f 272716 cdebconf_0.227.orig.tar.xz
 40d5ab5fa847da3cf2b2a3aad20b91a3f7b3c97f 139440 cdebconf_0.227-1arcadia1.debian.tar.xz
Checksums-Sha256:
 df2092bb5d4fe76c318adfd1cc756f78b48a668704b6e71e161143e7c782da58 272716 cdebconf_0.227.orig.tar.xz
 607b4bce081650ca6abcc93502f10770418c9afd873f9a944fb4c543b956c8e9 139440 cdebconf_0.227-1arcadia1.debian.tar.xz
Files:
 5c39434f2a1076e30389b07ddc229eaa 272716 cdebconf_0.227.orig.tar.xz
 ed65c201bc1d438975d35adc13c7c559 139440 cdebconf_0.227-1arcadia1.debian.tar.xz
