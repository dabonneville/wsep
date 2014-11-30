#!/usr/bin/env python
#-*- coding:utf-8-*-
import os
import sys
from pyes import *
from bird.items import BirdItem
INDEX_NAME='bird'

class SearchIndex(object):

    def SearchInit(self):
        self.conn = ES('10.8.178.163.1:9200', timeout=5)#Connect to ES
        try:
            self.conn.indices.delete_index(INDEX_NAME)
            #pass
        except:
            pass
        self.conn.indices.create_index(INDEX_NAME)#Create a new INDEX

        #Define the structure of the data format
        mapping = {u'info': {'boost': 1.0,
                          'index': 'analyzed',
                          'store': 'yes',
                          'type': u'string',
                          "indexAnalyzer":"ik",
                          "searchAnalyzer":"ik",
                          "term_vector" : "with_positions_offsets"},
                  u'title': {'boost': 4.0,
                             'index': 'analyzed',
                             'store': 'yes',
                             'type': u'string',
                             "indexAnalyzer":"ik",
                             "searchAnalyzer":"ik",
                             "term_vector" : "with_positions_offsets"},
                 u'time': {'boost': 1.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type': u'string',
                             "indexAnalyzer":"ik",
                             "searchAnalyzer":"ik",
                             "term_vector" : "with_positions_offsets"},
                 u'address': {'boost': 1.0,
                             'index': 'analyzed',
                             'store': 'yes',
                             'type': u'string',
                             "indexAnalyzer":"ik",
                             "searchAnalyzer":"ik",
                             "term_vector" : "with_positions_offsets"},
                 u'price': {'boost': 1.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type': u'string',
                             "indexAnalyzer":"ik",
                             "searchAnalyzer":"ik",
                             "term_vector" : "with_positions_offsets"},
                 u'category': {'boost': 1.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type': u'string',
                             "indexAnalyzer":"ik",
                             "searchAnalyzer":"ik",
                             "term_vector" : "with_positions_offsets"},
                 u'sort':  {'boost': 1.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type': u'string',
                             "term_vector" : "with_positions_offsets"
                             },                             
                 u'eventid': {'boost': 1.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type': 'long',
                             "not_value":0
                             },
                 u'city': {'boost': 2.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type': u'string',
                             "indexAnalyzer":"ik",
                             "searchAnalyzer":"ik",
                             "term_vector" : "with_positions_offsets"},
                u'area': {'boost': 2.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type': u'string',
                             "indexAnalyzer":"ik",
                             "searchAnalyzer":"ik",
                             "term_vector" : "with_positions_offsets"},                              
                 u'latitude': {'boost': 1.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type':  u'string',
                             "term_vector" : "with_positions_offsets"
                             },
                 u'longitude': {'boost': 1.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type':  u'string',
                             "term_vector" : "with_positions_offsets"
                             },
                 u'startDate': {'boost': 1.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type':  'date',
							},    
				u'endDate': {'boost': 1.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type':  'date',
							},				
                u'today':{'boost': 1.0,
                             'index': 'not_analyzed',
                             'store': 'yes',
                             'type':  'integer',
                             'not_value':0
                             },		                                                                                                               
                  u'url': {'boost': 1.0,
                             'index': 'analyzed',
                             'store': 'no',
                             'type': u'string',
                             "term_vector" : "with_positions_offsets"},
                  u'imgSrc': {'boost': 1.0,
                             'index': 'analyzed',
                             'store': 'yes',
                             'type': u'string',
                             "term_vector" : "with_positions_offsets"},                             
        }

        self.conn.indices.put_mapping("searchEngine-type", {'properties':mapping}, [INDEX_NAME])#Define the type

    def AddIndex(self,item):

        # print 'Adding Index item URL %s'% item['title']
        self.conn.index({'title':item['title'], \
                'url':item['url'],\
                'city':item['city'],\
                'info':item['info'],\
                'address':item['address'],\
				'time':item['time'],\
				'price':item['price'],\
				'category':item['category'],\
				'longitude':item['longitude'],\
				'latitude':item['latitude'],\
				'startDate':item['startDate'],\
				'endDate':item['endDate'],\
                'today':item['today'],\
				'imgSrc':item['imgSrc'],\
                'sort':item['sort'],\
                'eventid':item['eventid'],\
                'area':item['area']\
                },INDEX_NAME,'searchEngine-type')

    def IndexDone(self):
        self.conn.default_indices=[INDEX_NAME]#Set the default indices
        self.conn.indices.refresh()#Refresh the ES