toolsdir = $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/../tools/make)

bold = $(shell tput bold)
sgr = $(shell tput sgr0)
green = $(shell tput setaf 2)

DEB_SRC = $(toolsdir)/deb_src
CP = $(toolsdir)/cp
TOUCH = touch
define UPD_START =
@val=$$(printf "$@" | tail -c 60); \
[ "$$val" != "$@" ] && val="...$${val}"; \
printf "$(bold)UPDATE:$(sgr) %s ..." "$$val"
endef
DONE = @printf " $(green)done$(sgr)\n"
RM = rm -Rf
MKDIR = mkdir -p
