pkg_name = arcadia-installer
DI_VERSION ?= 20170615+deb9u3

srcdir := $(dir $(lastword $(MAKEFILE_LIST)))
override srcdir := $(abspath $(srcdir))
make_confdir := $(srcdir)/conf/make

ifdef DESTDIR
override DESTDIR := $(abspath $(DESTDIR))
endif

include $(make_confdir)/common.mk

d-i_source = debian-installer-$(DI_VERSION)
d-i_dsc = debian-installer_$(DI_VERSION).dsc
d-i_tar = debian-installer_$(DI_VERSION).tar.gz
d-i_conf = $(d-i_source)/build

web_sources = $(wildcard $(srcdir)/src/web/*)
web_install_targets = netboot config.php $(notdir $(web_sources))
web_install_targets := $(addprefix $(DESTDIR)$(datadir)/web/, $(web_install_targets))
web_host := localhost:8888

all: netboot

.PHONY: netboot
netboot: %: $(d-i_conf)/dest/%/debian-installer

install: netboot_install web_install

.PHONY: web_install
web_install: $(web_install_targets)

.PHONY: netboot_install
netboot_install: %_install: $(DESTDIR)$(datadir)/%

.PHONY: web_test
web_test: DESTDIR = test
web_test:
	$(MAKE) -f $(MAKEFILE_LIST) web_install DESTDIR=$(DESTDIR)
	$(INSTALL) -d $(DESTDIR)$(sysconfdir)
	@echo "[server]" >$(DESTDIR)$(sysconfdir)/$(pkg_name).ini
	@echo "base = 'http://$(web_host)'" >>$(DESTDIR)$(sysconfdir)/$(pkg_name).ini
	save_traps=$$(trap); \
	trap '$(RM) $(DESTDIR); eval "$$save_traps"' INT; \
	php -S $(web_host) -t $(DESTDIR)$(datadir)/web

.PHONY: $(DESTDIR)$(datadir)/netboot
$(DESTDIR)$(datadir)/netboot: $(d-i_conf)/dest/netboot/debian-installer
	$(INSTALL) -d "$@"
	set -e; \
	for arch in $(notdir $(call wildcard_d,$<)); do \
		$(INSTALL) -d "$@/$$arch"; \
		for file in linux initrd.gz; do \
			$(INSTALL) -t "$@/$$arch" "$</$$arch/$$file"; \
		done; \
	done

.PHONY: $(DESTDIR)$(datadir)/web/config.php
$(DESTDIR)$(datadir)/web/config.php: $(DESTDIR)$(datadir)/web
	@echo '<?php' >$@
	@echo '$$prefix = "$(DESTDIR)$(prefix)";' >>$@
	@echo '$$datarootdir = $$prefix ."/share";' >>$@
	@echo '$$datadir = $$datarootdir ."/$(pkg_name)";' >>$@
	@echo '$$sysconfdir = $$prefix ."/etc";' >>$@
	@echo '?>' >>$@


.PHONY: $(DESTDIR)$(datadir)/web/netboot
$(DESTDIR)$(datadir)/web/netboot: $(DESTDIR)$(datadir)/netboot \
$(DESTDIR)$(datadir)/web
	$(LINK_R) $< $@

.PHONY: $(DESTDIR)$(datadir)/web/%
$(DESTDIR)$(datadir)/web/%: src/web/% $(DESTDIR)$(datadir)/web
	$(INSTALL) $< $@

.PHONY: $(DESTDIR)$(datadir)/web
$(DESTDIR)$(datadir)/web:
	$(INSTALL) -d "$@"

$(d-i_conf)/dest/%/debian-installer: $(d-i_conf)/preferences.udeb.local \
$(d-i_conf)/pkg-lists/local
	$(MAKE) -C $(d-i_conf) rebuild_$* USE_UDEBS_FROM=stretch

$(d-i_conf)/preferences.udeb.local: conf/preferences.udeb.local $(d-i_source)
	@$(CP) $< $@

$(d-i_conf)/pkg-lists/local: $(d-i_conf)/localudebs
	@$(RM) $@
	@$(TOUCH) $@
	@for p in $$($(FIND) $< -name *.udeb); do \
		echo $$($(BASENAME) $$p | cut -d '_' -f 1) >>$@; \
	done

$(d-i_conf)/localudebs: udebs $(d-i_source)
	@$(RM) $@/*.udeb
	@for p in $$($(FIND) $< -name *.udeb); do \
		$(CP) "$$p" $@/; \
	done
	@$(TOUCH) $@

udebs: src/udebs/Makefile .FORCE
	@$(MKDIR) $@
	$(MAKE) -C $@ -f $< all \
		make_confdir=$(make_confdir) make_toolsdir=$(make_toolsdir)

$(d-i_source): $(d-i_dsc)
	@$(DPKG_SRC) -x "$<" $@

$(d-i_dsc): .FORCE
	@$(DEB_SRC) debian-installer=$(DI_VERSION)

clean: all_clean

.PHONY: all_clean udebs_clean %_clean
all_clean: udebs_clean
	-@[ -d $(d-i_conf) ] && $(MAKE) -C $(d-i_conf) all_clean || true

udebs_clean: %_clean: src/%/Makefile
	-@[ -d $* ] && $(MAKE) -C $* -f $< clean \
		make_confdir=$(make_confdir) make_toolsdir=$(make_toolsdir)

%_clean:
	-@[ -d $(d-i_conf) ] && $(MAKE) -C $(d-i_conf) clean_$* || true

distclean: udebs_distclean
	-$(RM) $(d-i_source) $(d-i_dsc) $(d-i_tar)

.PHONY: udebs_distclean
udebs_distclean: %_distclean: src/%/Makefile
	-@[ -d $* ] && $(MAKE) -C $* -f $< distclean \
		make_confdir=$(make_confdir) make_toolsdir=$(make_toolsdir)
ifneq ($(CURDIR),$(srcdir))
	-$(RM) $*
endif
