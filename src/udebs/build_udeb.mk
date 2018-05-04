make_confdir := $(srcdir)/conf/make

include $(make_confdir)/common.mk

ARCH := $(shell dpkg-architecture -qDEB_BUILD_ARCH)

deb_version = $(pkg_name)_$(pkg_version)
deb_dsc = $(deb_version).dsc
patches = $(sort $(wildcard $(srcdir)/src/patches/*.diff))
arcadia_version = $(pkg_version)$(basename $(notdir $(lastword $(patches))))
deb_source = $(pkg_name)-$(arcadia_version)

local_source := $(wildcard $(srcdir)/src/$(deb_source))
ifneq ($(local_source),)
deb_dsc := $(srcdir)/src/$(deb_dsc)
endif

all: $(CURDIR)

$(CURDIR): $(deb_source)
	@cd $< && dpkg-buildpackage
	@touch $@

clean:
	-$(RM) $(filter-out Makefile src,$(wildcard *))

$(deb_source): $(deb_dsc) $(patches)
	@$(DPKG_SRC) -x $< $@
	@cd $@ && \
	for p in $(wordlist 2,$(words $^),$^); do \
		patch -p1 <$$p; \
	done

ifeq ($(local_source),)
$(deb_dsc): .FORCE
	@$(DEB_SRC) $(pkg_name)=$(pkg_version)
else
$(deb_dsc): $(local_source)
	@cd $(dir $@) && $(DPKG_SRC) -b $<
endif
