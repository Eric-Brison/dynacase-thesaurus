# $Revision: 1.1 $, $Date: 2001/07/31 08:26:56 $
Summary:	PAM Modules to postgres connection
Summary(fr):	Module PAM pour la connection � une base postgres
Name:		pam_what_access
Version:	0.1.0
Release:	1
License:	GPL or BSD
Group:		Base
Source0:	ftp://ftp.souillac.anakeen.com/pub/anakeen/%{name}-%{version}.tar.gz
Vendor:         Anakeen           
URL:		http://www.anakeen.com
#BuildRequires:	pam-devel
#Requires:	make
Requires:	pam >= 0.72
BuildRoot:	%{_tmppath}/%{name}-%{version}-root-%(id -u -n)


%description
This PAM module is used to verify user accessibility with the WHAT database.
Only authent service is provided

%description -l fr
Ce module PAM permet de v�rifier les droits utilisateur via la base de donn�es de WHAT
Seul le service d'authenfication est fourni

%prep
%setup -q -n %{name}-%{version}


%build

%configure \
	--with-postgres --bindir="/lib/security"
%{__make}

%install
rm -rf $RPM_BUILD_ROOT
install -d $RPM_BUILD_ROOT/lib/security

%{__make} install DESTDIR=$RPM_BUILD_ROOT


%post   
%postun 

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(644,root,root,755)
%attr(0755,root,root) /lib/security/pam_what_access.so




%changelog
* Fri Jul 06 2001 Eric Brison <eric.brison@anakeen.com>
- Build first RPM


$Log: pam_what_access.spec,v $
Revision 1.1  2001/07/31 08:26:56  eric
first

