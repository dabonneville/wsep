# -*- coding: utf-8 -*-

# Define your item pipelines here
#
# Don't forget to add your pipeline to the ITEM_PIPELINES setting
# See: http://doc.scrapy.org/en/latest/topics/item-pipeline.html
from bird.conn import SearchIndex
from pybloomfilter import BloomFilter
from scrapy.exceptions import DropItem

class BirdPipeline(object):
    def __init__(self):
        self.bf = BloomFilter.open('filter.bloom')
        self.f_write = open('jingdong.txt','w')
        self.si = SearchIndex()
        self.si.SearchInit()

    def process_item(self, item, spider):
        # print '************%d pages visited!*****************' %len(self.bf)
        check=item['title']+item['city']+item['area']
        if self.bf.add(check):#True if item in the BF
            raise DropItem("Duplicate item found: %s" % item)
        else:
            self.save_to_file(item['url'],item['title'])
            self.si.AddIndex(item)
            return item

    def save_to_file(self,url,utitle):
        self.f_write.write(url)
        self.f_write.write('\t')
        self.f_write.write(utitle.encode('utf-8'))
        self.f_write.write('\n')

    def __del__(self):
        """docstring for __del__"""
        self.f_write.close()
        self.si.IndexDone()