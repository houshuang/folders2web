#!/usr/bin/env python
# -*- coding: utf-8 -*-
# doi2bibtex.py
#
# purpose:  fetch bibtex references using the Digital Object Identifier DOI
# author:   Filipe P. A. Fernandes
# e-mail:   ocefpaf@gmail
# web:      http://ocefpaf.tiddlyspot.com/
# created:  26-Jul-2010
# modified: Fri 14 Oct 2011 02:50:33 PM EDT
#
# obs: very messy script
# 25-Apr-2011 :: - Changed to class style
#                - Incorporated gscholar into script
#                - Search with doi in tryGoogle, much better results
#                         (seems obvious now, but just occurred to me today...)
#               - Accepts titles for both ADS and tryGoogle cases.
# TODO: add a argparse section that takes --open (open with webbrowser)
# bypass to search directly tryGoogle, getADS, getPANGAEA


import sys
import urllib2
import httplib2
from BeautifulSoup import BeautifulSoup

__version__ = '0.3.1'


#def search_doi(text):
    #"""Return a list with doi numbers found in a text.
    #http://stackoverflow.com/questions/27910/finding-a-doi-in-a-document-or-page
    #"""
    ##dval = re.compile(r'10.(\d)+/(\S)+')
    ## Good for web scrapping
    #dval = re.compile(r'(10.(\d)+/([^(\s\>\"\<)])+)')
    #doi = dval.findall(doi)
    #return doi


def usage():
    print("""\nUsage: doi2bibtex doinumber > ref.bib\n
    Search bibtex reference using the doi e.g.:
    doi2bibtex "doi:10.1016/j.ocemod.2003.12.003" """)


class Bibtex(object):
    """ Convert doi number to bibtex entries
    TODO: check a login system for crossref
    """
    def __init__(self, doi=None, title=None):
        """
        Input doi number ou title (actually any text/keyword.)
        Returns doi, encoded doi, and doi url or just the title.
        """
        _base_url = "http://dx.doi.org/"
        self.doi = doi
        self.title = title
        if doi:
            self._edoi = urllib2.quote(doi)
            self.url = _base_url + self._edoi  # Encoded doi.
        else:
            self.url = None

    def validate_doi(self):
        """Validate doi number and return the url."""
        # TODO: urllib2 does not redirect all possible doi(s).
        # once I figure out why I'll eliminate httplib2.
        h = httplib2.Http()
        h.request(self.url, "GET")
        request = httplib2.Http()
        try:
            self.header, self.html = request.request(self.url, "GET")
            self.paper_url = self.header['content-location']
            return self.paper_url
        except Exception, e:
            print("Could not resolve doi url at: " + self.url + " \n")
            print('Error: %s\n' % str(e))
            return None

    def _soupfy(self, url):
        """Returns a soup object."""
        html = urllib2.urlopen(url).read()
        self.soup = BeautifulSoup(html)
        return self.soup

    def getADS(self):
        """Get bibtex entry from doi using ADS database."""
        # direct approach: uri = "http://adsabs.harvard.edu/doi/"
        # search approach (better, some pages are not uri+doi,
        # but rather uri_bibcode):
        uri = "http://adsabs.harvard.edu/cgi-bin/basic_connect?qsearch="
        url = uri + self._edoi

        # Make soup and look for ADS bibcode.
        soup = self._soupfy(url)
        try:
            tag = soup.findAll('input', attrs={"name": "bibcode"})[0]
            bibcode = tag.get('value')
        except IndexError:  # TODO: Check if IndexError is the only error here.
            self.bibtex = None
            print("\nADS failed\n")
        else:
            uri = 'http://adsabs.harvard.edu/cgi-bin/nph-bib_query?bibcode='
            end = '&data_type=BIBTEX&db_key=AST%26nocookieset=1'
            url = uri + bibcode + end
            bib = urllib2.urlopen(url).readlines()
            # remove empty lines and query info
            self.bibtex = ''.join(bib[5:-1])
        finally:
            return self.bibtex

    def getPANGAEA(self):
        """ Get bibtex entry from doi using PANGEA database
        doi example: 10.1594/PANGAEA.726855
        TODO: add a return None when fails
        """
        uri = "http://doi.pangaea.de"
        url = uri + "/{}?format=citation_bibtex".format(self._edoi)
        self.bibtex = urllib2.urlopen(url).read()
        return self.bibtex

    def tryGoogle(self):
        """If you are feeling lucky."""
        self.bibtex = query(self.doi)
        return self.bibtex


def main(argv=None):
    """TODO: unittest with several doi searches."""
    if argv is None:
        argv = sys.argv
    if len(sys.argv) != 2:
        usage()
        sys.exit(2)

    doi = sys.argv[1]
    bib = Bibtex(doi=doi)

    def allfailed():
        """All failed message+google try."""
        bib.tryGoogle()
        bold = "\033[1m"
        reset = "\033[0;0m"
        url = bold + bib.url + reset  # FIXME: Has no meaning when using title
        msg = """Unable to resolve this DOI using database
        \nTry opening, \n\t{0}\nand download it manually.
        \n...or if you are lucky check the Google Scholar search below:
        \n{1}
        """.format(url, bib.bibtex)

        return msg

    if "PANGAEA" in doi:
        bib.getPANGAEA()
        print bib.bibtex
    else:
        bib.getADS()
        if bib.bibtex:
            print bib.bibtex
        else:
            print allfailed()

#--------------------------------------------------------------------
# https://github.com/venthur/gscholar
# gscholar - Get bibtex entries from Goolge Scholar
# Copyright (C) 2010  Bastian Venthur <venthur at debian org>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor,
# Boston, MA  02110-1301, USA.


"""
Library to query Google Scholar.
Call the method query with a string which contains the full search string.
Query will return a list of bibtex items.
"""

import re
import random
import hashlib
from htmlentitydefs import name2codepoint


def query(searchstr, allresults=False):
    """Return a list of bibtex items."""

    # fake google id (looks like it is a 16 elements hex)
    google_id = hashlib.md5(str(random.random())).hexdigest()[:16]

    GOOGLE_SCHOLAR_URL = "http://scholar.google.com"
    HEADERS = {'User-Agent': 'Mozilla/5.0',
               'Cookie': 'GSP=ID=%s:CF=4' % google_id}

    searchstr = '/scholar?q=' + urllib2.quote(searchstr)
    url = GOOGLE_SCHOLAR_URL + searchstr
    request = urllib2.Request(url, headers=HEADERS)
    response = urllib2.urlopen(request)
    html = response.read()
    html.decode('ascii', 'ignore')
    # grab the bibtex links
    tmp = get_biblinks(html)
    # follow the bibtex links to get the bibtex entries
    result = list()
    if allresults == False and len(tmp) != 0:
        tmp = [tmp[0]]
    for link in tmp:
        url = GOOGLE_SCHOLAR_URL + link
        request = urllib2.Request(url, headers=HEADERS)
        response = urllib2.urlopen(request)
        bib = response.read()
        result.append(bib)
    return ''.join(result)


def get_biblinks(html):
    """Return a list of biblinks from the html."""
    bibre = re.compile(r'<a href="(/scholar\.bib\?[^>]*)">')
    biblist = bibre.findall(html)
    # escape html enteties
    biblist = [re.sub('&(%s);' % '|'.join(name2codepoint), lambda m:
        unichr(name2codepoint[m.group(1)]), s) for s in biblist]
    return biblist

#--------------------------------------------------------------------
if __name__ == '__main__':
    sys.exit(main())

    """
    TODO: dict2bib and bib2dict
    TODO: create Unittest for the following cases
    create an "assert bibtex"
    ADS Fails
    doi2bibtex "10.1109/JOE.2007.895277" 10.1016/j.csr.2005.03.002
    PANGAEA
    doi2bibtex "10.1594/PANGAEA.726855"

    Tests
    doi = "10.1175/1520-0485(1999)029<1019:ITNTKR>2.0.CO;2"
    bib = Bibtex(doi)
    print bib.url

    doi = "10.1175/1520-0426(1993)010<0041:MTPOAA>2.0.CO;2"
    bib = Bibtex(doi)
    print bib.getADS()

    doi = "10.1594/PANGAEA.726855"
    bib = Bibtex(doi)
    print bib.getPANGAEA()

    doi = "10.1109/JOE.2007.895277"
    bib = Bibtex(doi)
    bib.tryGoogle()

    title = "Correlation scales, objective mapping, and absolute geostrophic \
    flow in the California Current"
    bib = Bibtex(doi)

    tryGoogle
    doi2bibtex "10.1016/0011-7471(73)90027-2"
    """