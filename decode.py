# python
# coding=utf-8

import sys

def crack_zhengfang( pwdhash, key="Encrypt01" ):
  len_passwd = len( pwdhash )
  len_key = len( key )
  pwdhash = pwdhash[: len_passwd/2][::-1] + pwdhash[len_passwd/2 :][::-1]
  passwd = ''
  Pos = 0
  for i in xrange( len_passwd ):
      Pos %= len_key
      Pos += 1
      strChar = pwdhash[i]
      KeyChar = key[Pos-1]
      ord_strChar = ord( strChar )
      ord_KeyChar = ord( KeyChar )
      if not 32 <= ( ord_strChar ^ ord_KeyChar ) <= 126 or not 0 <= ord_strChar <= 255:
          passwd += strChar
      else:
          passwd += chr( ord_strChar ^ ord_KeyChar )
  return passwd


if __name__ == '__main__':
    if len(sys.argv) != 2:
      print "Usage: crackZF.py passwdhash"
      sys.exit(1)
    else:
      print "Password:", crack_zhengfang( pwdhash=sys.argv[1], key="Encrypt01"
