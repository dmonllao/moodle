import sys
import csv
import numpy as np

from sklearn.cross_validation import train_test_split

# Linear.
from sklearn import datasets, linear_model
from sklearn.preprocessing import PolynomialFeatures
from sklearn.pipeline import make_pipeline

# Plot data.
import matplotlib.pyplot as plt

filepath = sys.argv[1]
examples = np.loadtxt(filepath, delimiter=', ')

# All columns but the last one.
X = np.array(examples[:,0:-1])

# Only the last one.
y = np.array(examples[:,-1:])

# Split examples into training set, validation set and test set (60% - 20% - 20%)
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.4)
X_val, X_test, y_val, y_test = train_test_split(X_test, y_test, test_size=0.5)

# Cross validation.
est = linear_model.RidgeCV(alphas=[0.1, 0.5, 1, 3, 5, 10, 20])
est.fit(X_val, y_val[:,0])
best_alpha = est.alpha_

# Estimators.
estimators = [('OLS', linear_model.LinearRegression()),
              ('Ridge', linear_model.Ridge(alpha=best_alpha, fit_intercept=False))]

degrees = [1, 2]
for degree in degrees:
    for name, est in estimators:

        print("\nEstimator %s, degree %d" % (name, degree))

        model = make_pipeline(PolynomialFeatures(degree), est)

        # Linear regressions wants y as a single dimension array.
        model.fit(X_train, y_train[:,0])

        # The mean square error.
        print("Residual sum of squares: %.2f"
              % np.mean((model.predict(X_test) - y_test) ** 2))

        # Explained variance score: 1 is perfect prediction.
        print('Variance score: %.2f' % model.score(X_test, y_test))

        print('Prediction for low num of accesses: %.2f' % (model.predict([[10, 10]])))
        print('Prediction for high num of accesses: %.2f' % (model.predict([[4000, 4000]])))
