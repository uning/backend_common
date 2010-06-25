/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra;

import java.util.Properties;

/**
 * The Interface IDriver.
 */
public interface IDriver {

	/**
	 * Connect to the Cassandra service.
	 * <p>
	 * <code>url</code> is following the schema similar with the JDBC. If using
	 * <code>Thrift</code>, it's something like:
	 * 
	 * <pre>
	 * thrift://localhost:9160
	 * </pre>
	 * 
	 * There is built-in support <code>thrift://</code>, and it can be extended.
	 * 
	 * @param url
	 *            the url to the server, for example:
	 *            <code>thrift://localhost:9160</code>
	 * @param info
	 *            the info
	 * @return a object that represents a connection to the URL.
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	IConnection connect(String url, Properties info) throws JassandraException;

	/**
	 * Retrieves whether the driver thinks that it can open a connection to the
	 * given URL. Typically drivers will return <code>true</code> if they
	 * understand the sub protocol specified in the URL and <code>false</code>
	 * if they do not.
	 * <p>
	 * See the documentation of {@link #connect(String, Properties)} for the
	 * format of the <code>url.</code>.
	 * 
	 * @param url
	 *            the URL of the database
	 * @return if this driver understands the given URL; otherwise
	 * @throws JassandraException
	 *             if a cassandra access error occurs
	 */
	boolean acceptsURL(String url) throws JassandraException;

	/**
	 * Retrieves the driver's major version number. Initially this should be 1.
	 * 
	 * @return this driver's major version number
	 */
	int getMajorVersion();

	/**
	 * Gets the driver's minor version number. Initially this should be 0.
	 * 
	 * @return this driver's minor version number
	 */
	int getMinorVersion();

}
