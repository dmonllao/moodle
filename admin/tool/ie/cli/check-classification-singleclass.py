import sys
import os
import time
import logging
import json

import numpy as np
from sklearn.utils import shuffle

from sklearn.cross_validation import train_test_split
from sklearn.linear_model import LogisticRegression

from sklearn.metrics import roc_curve, auc

# Local packages.
import logistic_utils
from roc_curve import RocCurve

np.set_printoptions(suppress=True)
np.set_printoptions(precision=5)
np.set_printoptions(threshold=np.inf)

# Simple run identifier (I want them ordered).
runid = str(int(time.time()))

# Missing arguments.
if len(sys.argv) < 4:
    result = dict()
    result['id'] = int(runid)
    result['exitcode'] = 1
    result['errors'] = ['Missing arguments, you should set the minimum phi value to validate the model and the accepted deviation. Received: ' + ' '.join(sys.argv)]
    print(json.dumps(result))
    sys.exit(result['exitcode'])

# Provided file dir.
filepath = sys.argv[1]
dirname = os.path.dirname(os.path.realpath(filepath))

# Percent to consider this test a success. Defaults to 0.7, which is the strong association boundary.
accepted_phi = float(sys.argv[2])
accepted_deviation = float(sys.argv[3])

# Logging.
logfile = os.path.join(dirname, runid + '.log')
logging.basicConfig(filename=logfile,level=logging.DEBUG)

# Examples loading.
[X, y] = logistic_utils.get_examples(filepath)

# Preprocess given data.
X = logistic_utils.scale(X)

solver = 'liblinear'
multi_class = 'ovr'
C = logistic_utils.get_c(X, y, solver, multi_class)

# Sanity check.
classes = [1, 0]
counts = []
y_array = np.array(y.T[0])
counts.append(np.count_nonzero(y_array))
counts.append(len(y_array) - np.count_nonzero(y_array))
logging.info('Number of examples by y value: %s' % str(counts))
balanced_classes = logistic_utils.check_classes_balance(classes, counts)
if balanced_classes != False:
    logging.warning(balanced_classes)

# Learning curve.
clf = LogisticRegression(solver=solver, tol=1e-1, C=C)
fig_filepath = logistic_utils.save_learning_curve(runid, dirname, 1, X, y, clf)
logging.info("Figure stored in " + fig_filepath)

# ROC curve.
roc_curve_plot = RocCurve(dirname, 2)

n_iterations = 200
accuracies = []
precisions = []
recalls = []
phis = []
aucs = []

for i in range(0, n_iterations):

    # Split examples into training set and test set (80% - 20%)
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2)

    # Init the classifier.
    clf = LogisticRegression(solver=solver, tol=1e-1, C=C)

    # Fit the training set. y should be an array-like.
    clf.fit(X_train, y_train[:,0])

    # Calculate scores.
    y_score = clf.decision_function(X_test)
    y_pred = clf.predict(X_test)

    # Transform it to an array.
    y_test = y_test.T[0]

    fpr, tpr, _ = roc_curve(y_test, y_score)
    aucs.append(auc(fpr, tpr))

    # Calculate accuracy, sensitivity and specificity.
    [acc, prec, rec, ph] = logistic_utils.calculate_metrics(y_test == 1, y_pred == 1)
    accuracies.append(acc)
    precisions.append(prec)
    recalls.append(rec)
    phis.append(ph)

    # Draw it.
    roc_curve_plot.add(fpr, tpr, 'Positives')

# Store the figure.
fig_filepath = roc_curve_plot.store(runid)
logging.info("Figure stored in " + fig_filepath)

# Return results.
result = logistic_utils.get_bin_results(accuracies, precisions, recalls, phis, aucs, accepted_phi, accepted_deviation)

# Add the run id to identify it in the caller.
result['id'] = int(runid)

logging.info("Accuracy: %.2f%%" % (result['accuracy'] * 100))
logging.info("Precision (predicted elements that are real): %.2f%%" % (result['precision'] * 100))
logging.info("Recall (real elements that are predicted): %.2f%%" % (result['recall'] * 100))
logging.info("Phi coefficient: %.2f%%" % (result['phi'] * 100))
logging.info("AUC standard desviation: %.4f" % (result['auc_deviation']))

# If we consider the classification as valid we store coeficients and intercepts.
if result['exitcode'] == 0:
    np.savetxt(os.path.join(dirname, runid + '.coef.txt'), clf.coef_)
    np.savetxt(os.path.join(dirname, runid + '.intercept.txt'), clf.intercept_)

print(json.dumps(result))
sys.exit(result['exitcode'])
