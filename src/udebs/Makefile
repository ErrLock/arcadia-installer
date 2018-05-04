srcdir := $(dir $(lastword $(MAKEFILE_LIST)))
override srcdir := $(abspath $(srcdir))
make_confdir := $(srcdir)/conf/make

include $(make_confdir)/common.mk

UDEBS := $(notdir $(patsubst %/,%,$(dir $(wildcard $(srcdir)/*/.))))
UDEBS_CLEAN := $(addsuffix _clean,$(UDEBS))
UDEBS_DISTCLEAN := $(addsuffix _distclean,$(UDEBS))

all: $(UDEBS)

clean: $(UDEBS_CLEAN)

distclean: $(UDEBS_DISTCLEAN)

.PHONY: $(UDEBS_CLEAN)
$(UDEBS_CLEAN): %_clean: %
	-@[ -d $* ] && $(MAKE) -C $* -f $</Makefile clean \
		make_confdir=$(make_confdir) make_toolsdir=$(make_toolsdir) || true

.PHONY: $(UDEBS_DISTCLEAN)
$(UDEBS_DISTCLEAN): %_distclean: %
	-@[ -d $* ] && $(MAKE) -C $* -f $</Makefile distclean \
		make_confdir=$(make_confdir) make_toolsdir=$(make_toolsdir) || true
ifneq ($(CURDIR),$(srcdir))
	-$(RM) $*
endif

$(UDEBS): %: $(srcdir)/% .FORCE
	@$(MKDIR) $@
	$(MAKE) -C $@ -f $</Makefile all \
		make_confdir=$(make_confdir) make_toolsdir=$(make_toolsdir)
	@[ $@ -ot $(CURDIR) ] || $(TOUCH) $(CURDIR)
