# $Revision: 1.15 $, $Date: 2007/06/27 13:36:29 $
%define cerbere         %(rpm -q --queryformat '%{VENDOR}' rpm |grep -q 'none' && echo 1 || echo 0)
%define pld		%(uname -o | grep -c PLD)
Summary:	PAM Modules to postgres connection
Summary(fr):	Module PAM pour la connection � une base postgres
Name:		pam_what_access
Version:	0.3.4
%if %{cerbere} || %{pld}
Release: 1
%else
Release: 1.fc7
%endif
License:	GPL or BSD
Group:		Base
Source0:	ftp://ftp.souillac.anakeen.com/pub/anakeen/%{name}-%{version}.tar.gz
Vendor:         Anakeen           
URL:		http://www.anakeen.com
#BuildRequires:	pam-devel
#Requires:	make
Requires:	pam >= 0.72
Requires:	postgresql-libs >= 7.2
BuildRoot:	%{_tmppath}/%{name}-%{version}-root-%(id -u -n)
BuildArchitectures: i686


%description
This PAM module is used to verify user accessibility with the WHAT database.
Only authent service is provided

%description -l fr
Ce module PAM permet de v�rifier les droits utilisateur via la base de donn�es de WHAT
Seul le service d'authentification est fourni

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
Revision 1.15  2007/06/27 13:36:29  eric
034

Revision 1.14  2006/05/09 08:21:06  jerome
- version++

Revision 1.13  2006/05/09 07:57:35  jerome
- Ajout support PLD

Revision 1.12  2006/04/12 07:58:01  eric
Fedora FC5

Revision 1.11  2005/09/06 09:40:00  eric
security prevent sql inject

Revision 1.10  2004/12/13 11:49:00  eric
correct _ in login

Revision 1.9  2003/08/12 13:39:06  eric
ajout option debug pour log debug

Revision 1.8  2002/08/06 11:38:18  eric
suppression require WHAT

Revision 1.7  2002/02/27 11:46:44  yannick
Prise en compte Postgresql 7.2

Revision 1.6  2002/02/26 09:43:28  yannick
Passage en Postgresql 7.2

Revision 1.5  2002/01/09 08:56:24  eric
change to new package WHAT

Revision 1.4  2001/09/12 09:18:40  eric
modif algo pour privilege groupes : compatible libwhat 0.4.8

Revision 1.3  2001/08/21 13:24:57  eric
modification pour nouvelle gestion des ACL

Revision 1.2  2001/08/21 13:21:30  eric
modification pour nouvelle gestion des ACL

Revision 1.1  2001/07/31 08:26:56  eric
first

