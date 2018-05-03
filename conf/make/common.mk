SHELL := /bin/sh
VPATH := $(srcdir)

ifndef pkg_name
pkg_name := $(notdir $(srcdir))
endif

prefix = /usr/local
exec_prefix = $(prefix)
bindir = $(exec_prefix)/bin
sbindir = $(exec_prefix)/sbin
libexecdir = $(exec_prefix)/libexec
datarootdir = $(prefix)/share
datadir = $(datarootdir)/$(pkg_name)
sysconfdir = $(prefix)/etc
sharedstatedir = $(prefix)/com
localstatedir = $(prefix)/var
runstatedir = $(localstatedir)/run
includedir = $(prefix)/include
docdir = $(datarootdir)/doc/$(pkg_name)
libdir = $(exec_prefix)/lib
localedir = $(datarootdir)/locale

ifndef make_confdir
make_confdir := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
endif

include $(make_confdir)/tools.mk

.SUFFIXES:
.PHONY: .FORCE all install uninstall clean distclean maintainer-clean info \
dist check
