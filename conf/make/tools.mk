ifndef make_toolsdir
make_toolsdir := $(srcdir)/tools/make
endif

DEB_SRC = $(wildcard $(make_toolsdir)/deb_src)
ifeq ($(DEB_SRC),)
DEB_SRC = apt-get source -d
endif

DPKG_SRC = $(wildcard $(make_toolsdir)/dpkg_src)
ifeq ($(DPKG_SRC),)
DEB_SRC = dpkg-source
endif

RM = $(wildcard $(make_toolsdir)/rm)
ifeq ($(RM),)
RM = rm -rf
endif

CP = $(wildcard $(make_toolsdir)/cp)
ifeq ($(CP),)
CP = cp -R
endif

INSTALL = $(wildcard $(make_toolsdir)/install)
ifeq ($(INSTALL),)
INSTALL = install
endif

MKDIR = $(wildcard $(make_toolsdir)/mkdir)
ifeq ($(MKDIR),)
MKDIR = $(INSTALL) -d
endif

FIND = $(wildcard $(make_toolsdir)/find)
ifeq ($(FIND),)
FIND = find
endif

TOUCH = $(wildcard $(make_toolsdir)/touch)
ifeq ($(TOUCH),)
TOUCH = touch
endif

BASENAME = $(wildcard $(make_toolsdir)/basename)
ifeq ($(BASENAME),)
BASENAME = basename
endif

LINK = $(wildcard $(make_toolsdir)/link)
ifeq ($(LINK),)
LINK = ln -sfr
endif
