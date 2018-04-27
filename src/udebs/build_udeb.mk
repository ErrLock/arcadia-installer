BUILDDIR ?= $(shell pwd)
SRCDIR ?= $(abspath $(dir $(firstword $(MAKEFILE_LIST))))

ifeq ($(BUILDDIR),$(SRCDIR))
BUILDDIR := $(BUILDDIR)/build
endif

ARCH := $(shell dpkg-architecture -qDEB_BUILD_ARCH)

patches = $(wildcard $(SRCDIR)/patches/*.diff)
patch_version = $(patsubst $(SRCDIR)/patches/%.diff,%,$(lastword $(patches)))
udeb = $(NAME)_$(VERSION)+$(patch_version)_$(ARCH).udeb

SHELL = /bin/sh
VPATH = $(BUILDDIR)

include $(SRCDIR)/../../../conf/tools.mk

.SUFFIXES:
.PHONY: .FORCE

.PHONY: all
all: $(udeb)

$(BUILDDIR):
	@$(MKDIR) $@

$(BUILDDIR)/$(udeb): $(NAME)-$(patch_version)
	@cd $< && dpkg-buildpackage

$(BUILDDIR)/$(NAME)-$(patch_version): $(NAME)-$(VERSION) $(patches)
	@$(RM) $@
	@$(CP) $< $@
	@cd $@ && \
		for p in $(wordlist 2,$(words $^),$^); do \
			patch -p1 <$$p; \
		done

$(BUILDDIR)/$(NAME)-$(VERSION): %/$(NAME)-$(VERSION): %
	@$(DEB_SRC) $< $(NAME) $(VERSION)
