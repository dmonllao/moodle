import os
import sys
import time
import logging
import json

import numpy as np
import pandas as pd

import nl_utils
import logistic_utils

import matplotlib.pyplot as plt
from scipy.spatial.distance import euclidean

from sklearn.feature_extraction.text import CountVectorizer
from sklearn.cluster import KMeans
from sklearn.metrics import silhouette_score

# Provided file dir.
filepath = sys.argv[1]
dirname = os.path.dirname(os.path.realpath(filepath))

# Simple run identifier (I want them ordered).
runid = str(int(time.time()))

# Logging.
logfile = os.path.join(dirname, runid + '.log')
logging.basicConfig(filename=logfile,level=logging.DEBUG)

train = pd.read_csv(filepath, header=0, delimiter=",", quoting=4)

texts = []

# Max number of clusters depending on the number of examples.
n_examples = len(train['cmid'])

max_clusters = int(n_examples) / 2
min_clusters = int(n_examples) / 10

# TODO Add optional params to overwrite min and max number of clusters.
if n_examples < (min_clusters + 1):
    result = dict()
    result['id'] = int(runid)
    result['exitcode'] = 1
    result['errors'] = ['Not enough examples to split them in clusters']
    print(json.dumps(result))
    sys.exit(result['exitcode'])


for i, text in enumerate(train['title']):
    texts.append(nl_utils.clean_text(text))
for i, text in enumerate(train['section']):
    texts[i] = texts[i] + ' ' + nl_utils.clean_text(text)
for i, text in enumerate(train['content']):
    texts[i] = texts[i] + ' ' + nl_utils.clean_text(text)
for i, text in enumerate(train['description1']):
    if text:
        texts[i] = texts[i] + ' ' + nl_utils.clean_text(text)
for i, text in enumerate(train['description2']):
    if text:
        texts[i] = texts[i] + ' ' + nl_utils.clean_text(text)

vectorizer = CountVectorizer(analyzer = "word",   \
                             tokenizer = None,    \
                             preprocessor = None, \
                             stop_words = None,   \
                             max_features = 5000)

X = vectorizer.fit_transform(texts)
X = X.toarray()

activityids = []
distances = []
silhouettes = []

for k in range(min_clusters, max_clusters):

    clusters = dict()

    clf = KMeans(n_clusters=k)
    cluster_labels = clf.fit_predict(X)

    # Calculate the distances to plot them.
    dist = 0
    for i, row in enumerate(X):
        x_centroid = cluster_labels[i]
        dist = dist + euclidean(row, clf.cluster_centers_[x_centroid])

    distances.append(dist)

    silhouettes.append(silhouette_score(X, cluster_labels))

    # Initialise lists.
    for i in range(k):
        clusters[i] = []

    for i, group in enumerate(cluster_labels):
        clusters[group].append(train['cmid'][i])

    activityids.append(clusters)

logistic_utils.save_elbow(runid, dirname, 1, distances)
logistic_utils.save_silhouette(runid, dirname, 2, silhouettes)

result = dict()
result['exitcode'] = 0
result['errors'] = []

best_k = silhouettes.index(max(silhouettes))
logging.info('Silhouettes: %s', json.dumps(silhouettes))
logging.info('Selected number of clusters: %i', (best_k + min_clusters))

result['clusters'] = activityids[best_k]

print(json.dumps(result))
sys.exit(result['exitcode'])
