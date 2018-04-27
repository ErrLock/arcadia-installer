DI_VERSION ?= 20170615+deb9u3
BUILDDIR ?= $(shell pwd)
SRCDIR ?= $(abspath $(dir $(firstword $(MAKEFILE_LIST))))

ifeq ($(BUILDDIR),$(SRCDIR))
BUILDDIR := $(BUILDDIR)/build
endif

d-i_name = debian-installer-$(DI_VERSION)
d-i = $(BUILDDIR)/installer/$(d-i_name)
d-i_conf = $(d-i)/build

SHELL = /bin/sh
VPATH = $(BUILDDIR):$(SRCDIR)/conf::$(SRCDIR)/src

include $(SRCDIR)/conf/tools.mk

.SUFFIXES:
.PHONY: .FORCE

.PHONY: all
all: netboot

.PHONY: clean
clean:
	rm -Rf $(BUILDDIR)/*

.PHONY: netboot
netboot: dest/netboot

$(BUILDDIR)/dest/netboot: $(BUILDDIR)/dest/%: conf.stamp
	$(MAKE) -C $(d-i_conf) rebuild_$* \
		USE_UDEBS_FROM=stretch \
		DEST=$(dir $@)

$(BUILDDIR)/conf.stamp: conf

.PHONY: conf
conf: $(d-i_conf)/preferences.udeb.local $(d-i_conf)/pkg-lists/local

$(d-i_conf)/preferences.udeb.local: preferences.udeb.local $(d-i)
	@$(CP) $< $@
	@$(TOUCH) $(BUILDDIR)/conf.stamp

$(d-i_conf)/pkg-lists/local: $(d-i_conf)/localudebs
	$(UPD_START)
	@rm -f $@
	@for p in $</*.udeb; do \
		echo $$(basename $$p | cut -d '_' -f 1) >>$@; \
	done
	@$(TOUCH) $(BUILDDIR)/conf.stamp
	$(DONE)

$(d-i_conf)/localudebs: udebs.list $(d-i)
	@$(RM) $@/*.udeb
	@for p in $$(cat $<); do \
		$(CP) $$p $@; \
	done
	@$(TOUCH) $(BUILDDIR)/conf.stamp

$(d-i): %/$(d-i_name): .FORCE
	@$(DEB_SRC) $* debian-installer $(DI_VERSION)

$(BUILDDIR)/udebs.list: udebs .FORCE
	$(UPD_START)
	@udebs=$$(find $(BUILDDIR)/udebs -name *.udeb); \
	if ! echo "$$udebs" | cmp -s - $@; then \
		echo "$$udebs" >$@; \
	else \
		for p in $$udebs; do \
			test $$p -nt $@ && touch -r $$p $@; \
		done; \
	fi; \
	true
	$(DONE)

$(BUILDDIR)/udebs: $(BUILDDIR)/%: $(SRCDIR)/src/% .FORCE
	$(MAKE) -C $< \
		BUILDDIR=$@
