
SHELL = /bin/sh

#### Start of system configuration section. ####

srcdir = src
topdir = /usr/local/include/ruby-1.9.1
hdrdir = /usr/local/include/ruby-1.9.1
arch_hdrdir = /usr/local/include/ruby-1.9.1/$(arch)
VPATH = $(srcdir):$(arch_hdrdir)/ruby:$(hdrdir)/ruby
prefix = $(DESTDIR)/usr/local
exec_prefix = $(prefix)
rubylibprefix = $(libdir)/$(RUBY_BASE_NAME)
bindir = $(exec_prefix)/bin
sbindir = $(exec_prefix)/sbin
libexecdir = $(exec_prefix)/libexec
datarootdir = $(prefix)/share
datadir = $(datarootdir)
sysconfdir = $(prefix)/etc
sharedstatedir = $(prefix)/com
localstatedir = $(prefix)/var
includedir = $(prefix)/include
oldincludedir = $(DESTDIR)/usr/include
docdir = $(datarootdir)/doc/$(PACKAGE)
infodir = $(datarootdir)/info
htmldir = $(docdir)
dvidir = $(docdir)
pdfdir = $(docdir)
psdir = $(docdir)
libdir = $(exec_prefix)/lib
localedir = $(datarootdir)/locale
mandir = $(datarootdir)/man
ridir = $(datarootdir)/$(RI_BASE_NAME)
sitedir = $(rubylibprefix)/site_ruby
vendordir = $(rubylibprefix)/vendor_ruby
rubyhdrdir = $(includedir)/$(RUBY_BASE_NAME)-$(ruby_version)
sitehdrdir = $(rubyhdrdir)/site_ruby
vendorhdrdir = $(rubyhdrdir)/vendor_ruby
rubylibdir = $(rubylibprefix)/$(ruby_version)
archdir = $(rubylibdir)/$(arch)
sitelibdir = $(sitedir)/$(ruby_version)
sitearchdir = $(sitelibdir)/$(sitearch)
vendorlibdir = $(vendordir)/$(ruby_version)
vendorarchdir = $(vendorlibdir)/$(sitearch)

CC = gcc
CXX = g++
LIBRUBY = $(LIBRUBY_SO)
LIBRUBY_A = lib$(RUBY_SO_NAME)-static.a
LIBRUBYARG_SHARED = -l$(RUBY_SO_NAME)
LIBRUBYARG_STATIC = -l$(RUBY_SO_NAME)-static
OUTFLAG = -o 
COUTFLAG = -o 

RUBY_EXTCONF_H = 
cflags   =  $(optflags) $(debugflags) $(warnflags)
optflags = -O3
debugflags = -ggdb
warnflags = -Wextra -Wno-unused-parameter -Wno-parentheses -Wpointer-arith -Wwrite-strings -Wno-missing-field-initializers -Wshorten-64-to-32 -Wno-long-long
CFLAGS   = -fno-common $(cflags)  -fno-common -pipe -Wall 
INCFLAGS = -I. -I$(arch_hdrdir) -I$(hdrdir)/ruby/backward -I$(hdrdir) -I$(srcdir)
DEFS     = 
CPPFLAGS =  -D_XOPEN_SOURCE -D_DARWIN_C_SOURCE $(DEFS) $(cppflags)
CXXFLAGS = $(CFLAGS) $(cxxflags)
ldflags  = -L. -L/usr/local/lib -framework Carbon -framework ApplicationServices
dldflags = -Wl,-undefined,dynamic_lookup -Wl,-multiply_defined,suppress -Wl,-flat_namespace
ARCH_FLAG = 
DLDFLAGS = $(ldflags) $(dldflags)
LDSHARED = $(CC) -dynamic -bundle
LDSHAREDXX = $(CXX) -dynamic -bundle
AR = ar
EXEEXT = 

RUBY_BASE_NAME = ruby
RUBY_INSTALL_NAME = ruby19
RUBY_SO_NAME = ruby.1.9.1
arch = x86_64-darwin10.7.3
sitearch = $(arch)
ruby_version = 1.9.1
ruby = /usr/local/bin/ruby19
RUBY = $(ruby)
RM = rm -f
RM_RF = $(RUBY) -run -e rm -- -rf
RMDIRS = $(RUBY) -run -e rmdir -- -p
MAKEDIRS = mkdir -p
INSTALL = /usr/bin/install -c
INSTALL_PROG = $(INSTALL) -m 0755
INSTALL_DATA = $(INSTALL) -m 644
COPY = cp

#### End of system configuration section. ####

preload = 

libpath = . $(libdir)
LIBPATH =  -L. -L$(libdir)
DEFFILE = 

CLEANFILES = mkmf.log
DISTCLEANFILES = 
DISTCLEANDIRS = 

extout = 
extout_prefix = 
target_prefix = 
LOCAL_LIBS = 
LIBS = $(LIBRUBYARG_SHARED)  -lpthread -ldl -lobjc 
SRCS = rbae.c SendThreadSafe.c
OBJS = rbae.o SendThreadSafe.o
TARGET = ae
DLLIB = $(TARGET).bundle
EXTSTATIC = 
STATIC_LIB = 

BINDIR        = $(bindir)
RUBYCOMMONDIR = $(sitedir)$(target_prefix)
RUBYLIBDIR = /Volumes/Home/stian/src/folders2web/vendor/gems/rb-appscript-0.6.1/lib$(target_prefix)
RUBYARCHDIR = /Volumes/Home/stian/src/folders2web/vendor/gems/rb-appscript-0.6.1/lib$(target_prefix)
HDRDIR        = $(rubyhdrdir)/ruby$(target_prefix)
ARCHHDRDIR    = $(rubyhdrdir)/$(arch)/ruby$(target_prefix)

TARGET_SO     = $(DLLIB)
CLEANLIBS     = $(TARGET).bundle 
CLEANOBJS     = *.o  *.bak

all:    $(DLLIB)
static: $(STATIC_LIB)
.PHONY: all install static install-so install-rb
.PHONY: clean clean-so clean-rb

clean-rb-default::
clean-rb::
clean-so::
clean: clean-so clean-rb-default clean-rb
		@-$(RM) $(CLEANLIBS) $(CLEANOBJS) $(CLEANFILES)

distclean-rb-default::
distclean-rb::
distclean-so::
distclean: clean distclean-so distclean-rb-default distclean-rb
		@-$(RM) Makefile $(RUBY_EXTCONF_H) conftest.* mkmf.log
		@-$(RM) core ruby$(EXEEXT) *~ $(DISTCLEANFILES)
		@-$(RMDIRS) $(DISTCLEANDIRS)

realclean: distclean
install: install-so install-rb

install-so: $(RUBYARCHDIR)
install-so: $(RUBYARCHDIR)/$(DLLIB)
$(RUBYARCHDIR)/$(DLLIB): $(DLLIB)
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_PROG) $(DLLIB) $(@D)
install-rb: pre-install-rb install-rb-default
install-rb-default: pre-install-rb-default
pre-install-rb: Makefile
pre-install-rb-default: Makefile
pre-install-rb-default: $(RUBYLIBDIR)/_aem
install-rb-default: $(RUBYLIBDIR)/_aem/aemreference.rb
$(RUBYLIBDIR)/_aem/aemreference.rb: src/lib/_aem/aemreference.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_aem/aemreference.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_aem/codecs.rb
$(RUBYLIBDIR)/_aem/codecs.rb: src/lib/_aem/codecs.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_aem/codecs.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_aem/connect.rb
$(RUBYLIBDIR)/_aem/connect.rb: src/lib/_aem/connect.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_aem/connect.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_aem/encodingsupport.rb
$(RUBYLIBDIR)/_aem/encodingsupport.rb: src/lib/_aem/encodingsupport.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_aem/encodingsupport.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_aem/findapp.rb
$(RUBYLIBDIR)/_aem/findapp.rb: src/lib/_aem/findapp.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_aem/findapp.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_aem/mactypes.rb
$(RUBYLIBDIR)/_aem/mactypes.rb: src/lib/_aem/mactypes.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_aem/mactypes.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_aem/send.rb
$(RUBYLIBDIR)/_aem/send.rb: src/lib/_aem/send.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_aem/send.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_aem/typewrappers.rb
$(RUBYLIBDIR)/_aem/typewrappers.rb: src/lib/_aem/typewrappers.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_aem/typewrappers.rb $(@D)
pre-install-rb-default: $(RUBYLIBDIR)/_appscript
install-rb-default: $(RUBYLIBDIR)/_appscript/defaultterminology.rb
$(RUBYLIBDIR)/_appscript/defaultterminology.rb: src/lib/_appscript/defaultterminology.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_appscript/defaultterminology.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_appscript/referencerenderer.rb
$(RUBYLIBDIR)/_appscript/referencerenderer.rb: src/lib/_appscript/referencerenderer.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_appscript/referencerenderer.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_appscript/reservedkeywords.rb
$(RUBYLIBDIR)/_appscript/reservedkeywords.rb: src/lib/_appscript/reservedkeywords.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_appscript/reservedkeywords.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_appscript/safeobject.rb
$(RUBYLIBDIR)/_appscript/safeobject.rb: src/lib/_appscript/safeobject.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_appscript/safeobject.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/_appscript/terminology.rb
$(RUBYLIBDIR)/_appscript/terminology.rb: src/lib/_appscript/terminology.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/_appscript/terminology.rb $(@D)
pre-install-rb-default: $(RUBYLIBDIR)
install-rb-default: $(RUBYLIBDIR)/aem.rb
$(RUBYLIBDIR)/aem.rb: src/lib/aem.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/aem.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/appscript.rb
$(RUBYLIBDIR)/appscript.rb: src/lib/appscript.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/appscript.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/kae.rb
$(RUBYLIBDIR)/kae.rb: src/lib/kae.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/kae.rb $(@D)
install-rb-default: $(RUBYLIBDIR)/osax.rb
$(RUBYLIBDIR)/osax.rb: src/lib/osax.rb
	@-$(MAKEDIRS) $(@D)
	$(INSTALL_DATA) src/lib/osax.rb $(@D)
$(RUBYARCHDIR):
	$(MAKEDIRS) $@
$(RUBYLIBDIR)/_aem:
	$(MAKEDIRS) $@
$(RUBYLIBDIR)/_appscript:
	$(MAKEDIRS) $@
$(RUBYLIBDIR):
	$(MAKEDIRS) $@

site-install: site-install-so site-install-rb
site-install-so: install-so
site-install-rb: install-rb

.SUFFIXES: .c .m .cc .cxx .cpp .C .o

.cc.o:
	$(CXX) $(INCFLAGS) $(CPPFLAGS) $(CXXFLAGS) $(COUTFLAG)$@ -c $<

.cxx.o:
	$(CXX) $(INCFLAGS) $(CPPFLAGS) $(CXXFLAGS) $(COUTFLAG)$@ -c $<

.cpp.o:
	$(CXX) $(INCFLAGS) $(CPPFLAGS) $(CXXFLAGS) $(COUTFLAG)$@ -c $<

.C.o:
	$(CXX) $(INCFLAGS) $(CPPFLAGS) $(CXXFLAGS) $(COUTFLAG)$@ -c $<

.c.o:
	$(CC) $(INCFLAGS) $(CPPFLAGS) $(CFLAGS) $(COUTFLAG)$@ -c $<

$(DLLIB): $(OBJS) Makefile
	@-$(RM) $(@)
	$(LDSHARED) -o $@ $(OBJS) $(LIBPATH) $(DLDFLAGS) $(LOCAL_LIBS) $(LIBS)



$(OBJS): $(hdrdir)/ruby.h $(hdrdir)/ruby/defines.h $(arch_hdrdir)/ruby/config.h
