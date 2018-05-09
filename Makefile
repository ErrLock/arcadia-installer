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
web_install_targets = netboot
web_install_targets += config.php $(notdir $(web_sources))
web_install_targets := $(addprefix $(DESTDIR)$(datadir)/web/, $(web_install_targets))
web_host := localhost:8888

all: netboot

include udebs.d

.PHONY: netboot
netboot: %: $(d-i_conf)/dest/%/debian-installer

install: netboot_install web_install

.PHONY: web_install
web_install: $(web_install_targets)
	$(INSTALL) -d $(DESTDIR)$(sharedstatedir)

.PHONY: netboot_install
netboot_install: %_install: $(DESTDIR)$(datadir)/%

.PHONY: web_test
web_test: DESTDIR = test
web_test: tools/preconf
	$(MAKE) -f $(MAKEFILE_LIST) web_install DESTDIR=$(DESTDIR)
ifdef conf
	[ -f "$(conf)" ]
	$< "$(DESTDIR)$(datadir)/web" \
		'config' "$(conf)"
endif
ifdef preseed
	[ -f "$(preseed)" ]
	$< "$(DESTDIR)$(datadir)/web" \
		'preseed' "$(preseed)"
endif
	save_traps=$$(trap); \
	trap '$(RM) $(DESTDIR); eval "$$save_traps"' INT; \
	php -S $(web_host) -t $(DESTDIR)$(datadir)/web

$(DESTDIR)$(sysconfdir):
	$(INSTALL) -d "$@";

.PHONY: $(DESTDIR)$(sysconfdir)/$(pkg_name).ini
$(DESTDIR)$(sysconfdir)/$(pkg_name).ini: $(DESTDIR)$(sysconfdir)
	echo "[server]" >"$@"; \
	echo "salt = 'arcadia-installer'" >>"$@"; \

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
	echo '<?php' >$@; \
	echo 'define('\''ARCADIA_PKGNAME'\'', "$(pkg_name)");' >>$@; \
	echo 'define('\''ARCADIA_SYSCONFDIR'\'', "$(DESTDIR)$(sysconfdir)");' >>$@; \
	echo 'define('\''ARCADIA_SHAREDSTATEDIR'\'', "$(DESTDIR)$(sharedstatedir)");' >>$@; \
	echo 'set_include_path(__DIR__ . PATH_SEPARATOR . get_include_path());' >>$@; \
	echo '?>' >>$@


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
	@$(CP) "$<" "$@"

$(d-i_conf)/pkg-lists/local: $(d-i_conf)/localudebs
	@printf "" >"$@"
	@for p in $$($(FIND) $< -name *.udeb); do \
		echo $$($(BASENAME) "$$p" | cut -d '_' -f 1) >>"$@"; \
	done

$(d-i_conf)/localudebs: udebs $(d-i_source)
	@$(RM) $@/*.udeb
	@for p in $$($(FIND) $< -name '*.udeb'); do \
		$(CP) "$$p" "$@/"; \
	done
	@$(TOUCH) "$@"

udebs.d: udebs.list
	@echo "udebs = " >"$@"
	@for p in $$(cat "$<"); do \
		dsc=$${p#$(srcdir)/}; \
		pkg=$${dsc##*/}; \
		pkg=$${pkg%.*}; \
		pkg_name=$${pkg%%_*}; \
		pkg_version=$${pkg#*_}; \
		pkg_version=$${pkg_version%-*}; \
		udeb_src="udebs/$$pkg_name-$$pkg_version"; \
		echo "udebs += $$udeb_src" >>$@; \
		echo "$$udeb_src: $$dsc" >>$@; \
	done

udebs: $(udebs)
	$(TOUCH) "$@"

udebs/%:
	$(MKDIR) udebs
	$(DPKG_SRC) -x "$<" "$@"
	cd "$@" && debuild

udebs.list: .FORCE
	@dsc_list=$$($(FIND) $(srcdir)/src/udebs -iname '*.dsc'); \
	if printf "%s" "$$dsc_list" | cmp -s - "$@"; then \
		for p in $$dsc_list; do \
			if [ "$$p" -nt "$@" ]; then \
				echo "newer"; \
				$(TOUCH) "$@"; \
				break; \
			fi; \
		done; \
	else \
		printf "%s" "$$dsc_list" >"$@"; \
	fi

$(d-i_source): $(d-i_dsc)
	@$(DPKG_SRC) -x "$<" "$@"

$(d-i_dsc): .FORCE
	@$(DEB_SRC) debian-installer=$(DI_VERSION)

clean: udebs_clean
	-[ -d $(d-i_conf) ] && $(MAKE) -C $(d-i_conf) all_clean || true

.PHONY: udebs_clean
udebs_clean:
	-$(RM) udebs/*

distclean:
	-$(RM) $(d-i_source) $(d-i_dsc) $(d-i_tar) udebs udebs.list udebs.d
