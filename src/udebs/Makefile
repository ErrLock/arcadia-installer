UDEBS ?= partman-auto
BUILDDIR ?= $(shell pwd)
SRCDIR ?= $(abspath $(dir $(firstword $(MAKEFILE_LIST))))

ifeq ($(BUILDDIR),$(SRCDIR))
BUILDDIR := $(BUILDDIR)/build
endif

SHELL = /bin/sh

.SUFFIXES:
.PHONY: .FORCE

.PHONY: all
all: $(UDEBS)

.PHONY: $(UDEBS)
$(UDEBS):
	$(MAKE) -C $(SRCDIR)/$@ \
		BUILDDIR=$(BUILDDIR)/$@
