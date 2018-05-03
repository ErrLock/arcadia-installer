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
