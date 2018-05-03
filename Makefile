pkg_name = arcadia-installer
DI_VERSION ?= 20170615+deb9u3

srcdir := $(dir $(lastword $(MAKEFILE_LIST)))
override srcdir := $(abspath $(srcdir))
make_confdir := $(srcdir)/conf/make

include $(make_confdir)/common.mk

d-i_source = debian-installer-$(DI_VERSION)
d-i_dsc = debian-installer_$(DI_VERSION).dsc
d-i_tar = debian-installer_$(DI_VERSION).tar.gz
d-i_conf = $(d-i_source)/build

all: netboot

.PHONY: netboot
netboot: %: $(d-i_conf)/dest/%

$(d-i_conf)/dest/%: $(d-i_source)
	$(MAKE) -C $(d-i_conf) rebuild_$* USE_UDEBS_FROM=stretch

$(d-i_source): $(d-i_dsc)
	@$(DPKG_SRC) -x "$<" $@

$(d-i_dsc): .FORCE
	@$(DEB_SRC) debian-installer=$(DI_VERSION)

clean: all_clean

.PHONY: all_clean %_clean
all_clean:
	-@[ -d $(d-i_conf) ] && $(MAKE) -C $(d-i_conf) all_clean || true

%_clean:
	-@[ -d $(d-i_conf) ] && $(MAKE) -C $(d-i_conf) clean_$* || true

distclean:
	-$(RM) $(d-i_source) $(d-i_dsc) $(d-i_tar)	
